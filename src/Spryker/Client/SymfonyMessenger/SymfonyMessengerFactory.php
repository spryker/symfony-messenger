<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger;

use Psr\Container\ContainerInterface;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Queue\Model\Adapter\AdapterInterface;
use Spryker\Client\SymfonyMessenger\Adapter\SymfonyMessengerQueueAdapter;
use Spryker\Client\SymfonyMessenger\Consumer\Consumer;
use Spryker\Client\SymfonyMessenger\Consumer\ConsumerInterface;
use Spryker\Client\SymfonyMessenger\Control\QueueControl;
use Spryker\Client\SymfonyMessenger\Control\QueueControlInterface;
use Spryker\Client\SymfonyMessenger\MessageBus\Container\DefaultBusLocatorContainer;
use Spryker\Client\SymfonyMessenger\MessageBus\Container\QueueBusLocatorContainer;
use Spryker\Client\SymfonyMessenger\MessageBus\MessageBusBuilder;
use Spryker\Client\SymfonyMessenger\MessageBus\MessageBusBuilderInterface;
use Spryker\Client\SymfonyMessenger\Receiver\QueueReceiver;
use Spryker\Client\SymfonyMessenger\Receiver\ReceiverInterface;
use Spryker\Client\SymfonyMessenger\Sender\QueueSender;
use Spryker\Client\SymfonyMessenger\Sender\QueueSenderInterface;
use Spryker\Client\SymfonyMessenger\Sender\Sender;
use Spryker\Client\SymfonyMessenger\Sender\SenderInterface;
use Spryker\Client\SymfonyMessenger\Sender\SenderLocatorBuilder;
use Spryker\Client\SymfonyMessenger\Sender\SenderLocatorBuilderInterface;
use Spryker\Client\SymfonyMessenger\Stamp\QueueStampStackBuilder;
use Spryker\Client\SymfonyMessenger\Stamp\StampStackBuilderInterface;
use Spryker\Client\SymfonyMessenger\Transport\Amqp\AmqpTransportFactory;
use Spryker\Client\SymfonyMessenger\Transport\Amqp\OptimizedAmqpDecoder;
use Spryker\Client\SymfonyMessenger\Worker\WorkerBuilder;
use Spryker\Client\SymfonyMessenger\Worker\WorkerBuilderInterface;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactory;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * @method \Spryker\Client\SymfonyMessenger\SymfonyMessengerConfig getConfig()
 */
class SymfonyMessengerFactory extends AbstractFactory
{
    /**
     * Adapter is stored as static in order to not build router and related dependencies multiple times.
     */
    protected static ?AdapterInterface $queueAdapter = null;

    protected static ?TransportInterface $defaultQueueTransport = null;

    public function createQueueSender(): QueueSenderInterface
    {
        return new QueueSender($this->createQueueMessageBusBuilder(), $this->getConfig(), $this->createQueueStampStackBuilder());
    }

    public function createQueueReceiver(): ReceiverInterface
    {
        return new QueueReceiver(
            $this->createDefaultQueueTransport(),
            $this->createSerializer(),
            $this->createQueueSender(),
        );
    }

    public function createQueueMessageBusBuilder(): MessageBusBuilderInterface
    {
        return new MessageBusBuilder($this->createQueueBusLocatorContainer());
    }

    public function createSender(): SenderInterface
    {
        return new Sender($this->createMessageBusBuilder(), $this->getConfig(), $this->getMessageMappingProviderPlugins());
    }

    public function createMessageBusBuilder(): MessageBusBuilderInterface
    {
        return new MessageBusBuilder($this->createBusLocatorContainer());
    }

    public function createQueueBusLocatorContainer(): ContainerInterface
    {
        return new QueueBusLocatorContainer($this->getConfig(), $this->createSenderLocatorBuilder());
    }

    public function createBusLocatorContainer(): ContainerInterface
    {
        return new DefaultBusLocatorContainer($this->getConfig(), $this->createSenderLocatorBuilder(), $this->getMessageMappingProviderPlugins());
    }

    public function createSenderLocatorBuilder(): SenderLocatorBuilderInterface
    {
        return new SenderLocatorBuilder($this->getConfig(), $this->getAvailableTransports(), $this->getMessageMappingProviderPlugins());
    }

    /**
     * Callable parameters array $options is transport options array. See Symfony Messenger documentation for details or specific transport factory.
     * Transport stored in the closure in order to delay its creation until it is actually needed.
     *
     * @return array<string, \Closure>
     */
    public function getAvailableTransports(): array
    {
        $availableTransport = [
            $this->getConfig()::TRANSPORT_AMQP => function (array $options = []): TransportInterface {
                return $this->createTransport($this->getConfig()->getAmqpConnectionDSN(), $options);
            },
        ];

        foreach ($this->getAvailableTransportProviderPlugins() as $plugin) {
            foreach ($plugin->getTransportDSNByTransportName() as $transportName => $dsn) {
                $availableTransport[$transportName] = function (array $options = []) use ($dsn): TransportInterface {
                    return $this->createTransport($dsn, $options);
                };
            }
        }

        return $availableTransport;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTransport(string $dsn, array $options = []): TransportInterface
    {
        return (new TransportFactory($this->getInstalledTransportFactories()))
            ->createTransport($dsn, $options, $this->createSerializer());
    }

    /**
     * @return array<\Symfony\Component\Messenger\Transport\TransportFactoryInterface>
     */
    public function getInstalledTransportFactories(): array
    {
        $transportFactories = [
            new AmqpTransportFactory(),
        ];

        foreach ($this->getTransportFactoryProviderPlugins() as $plugin) {
            $transportFactories = array_merge(
                $transportFactories,
                $plugin->getTransportFactories(),
            );
        }

        return $transportFactories;
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\TransportFactoryProviderPluginInterface>
     */
    public function getTransportFactoryProviderPlugins(): array
    {
        return $this->getProvidedDependency(SymfonyMessengerDependencyProvider::PLUGINS_TRANSPORT_FACTORY_PROVIDER);
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface>
     */
    public function getMessageMappingProviderPlugins(): array
    {
        return $this->getProvidedDependency(SymfonyMessengerDependencyProvider::PLUGINS_MESSAGE_MAPPING_PROVIDER);
    }

    public function createSerializer(): SerializerInterface
    {
        return $this->getConfig()->isOptimizedDecodeEnabled() ? $this->createOptimizedAmqpDecoder() : $this->createSymfonySerializer();
    }

    public function createQueueAdapter(): AdapterInterface
    {
        if (static::$queueAdapter === null) {
            static::$queueAdapter = new SymfonyMessengerQueueAdapter(
                $this->createQueueSender(),
                $this->createQueueReceiver(),
                $this->createQueueControl(),
            );
        }

        return static::$queueAdapter;
    }

    public function createQueueStampStackBuilder(): StampStackBuilderInterface
    {
        return new QueueStampStackBuilder($this->getAdditionalQueueStamps());
    }

    /**
     * @return array<\Closure>
     */
    public function getAdditionalQueueStamps(): array
    {
        return [];
    }

    public function createQueueControl(): QueueControlInterface
    {
        $transport = $this->createDefaultQueueTransport();

        return new QueueControl($transport);
    }

    public function createDefaultQueueTransport(): TransportInterface
    {
        if (static::$defaultQueueTransport === null) {
            static::$defaultQueueTransport = $this->createTransport(
                $this->getConfig()->getAmqpConnectionDSN(),
                $this->getConfig()->getQueueTransportConfiguration()['default'] ?? [],
            );
        }

        return static::$defaultQueueTransport;
    }

    public function createMessengerWorkerBuilder(): WorkerBuilderInterface
    {
        return new WorkerBuilder($this->createMessageBusBuilder(), $this->getAvailableTransports());
    }

    public function createConsumer(): ConsumerInterface
    {
        return new Consumer($this->createMessengerWorkerBuilder(), $this->getGroupAwareTransportsPlugins());
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\AvailableTransportProviderPluginInterface>
     */
    public function getAvailableTransportProviderPlugins(): array
    {
        return $this->getProvidedDependency(SymfonyMessengerDependencyProvider::PLUGINS_AVAILABLE_TRANSPORT_PROVIDER);
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\GroupAwareTransportsPluginInterface>
     */
    public function getGroupAwareTransportsPlugins(): array
    {
        return $this->getProvidedDependency(SymfonyMessengerDependencyProvider::PLUGINS_GROUP_AWARE_TRANSPORTS_PLUGIN);
    }

    public function createOptimizedAmqpDecoder(): OptimizedAmqpDecoder
    {
        return new OptimizedAmqpDecoder();
    }

    public function createSymfonySerializer(): Serializer
    {
        return new Serializer();
    }
}
