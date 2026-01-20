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
use Spryker\Client\SymfonyMessenger\Control\QueueControl;
use Spryker\Client\SymfonyMessenger\Control\QueueControlInterface;
use Spryker\Client\SymfonyMessenger\MessageBus\Container\BusLocatorContainer;
use Spryker\Client\SymfonyMessenger\MessageBus\MessageBusBuilderInterface;
use Spryker\Client\SymfonyMessenger\MessageBus\QueueMessageBusBuilder;
use Spryker\Client\SymfonyMessenger\Receiver\QueueReceiver;
use Spryker\Client\SymfonyMessenger\Receiver\ReceiverInterface;
use Spryker\Client\SymfonyMessenger\Sender\QueueSender;
use Spryker\Client\SymfonyMessenger\Sender\QueueSenderInterface;
use Spryker\Client\SymfonyMessenger\Sender\SenderLocatorBuilder;
use Spryker\Client\SymfonyMessenger\Sender\SenderLocatorBuilderInterface;
use Spryker\Client\SymfonyMessenger\Stamp\QueueStampStackBuilder;
use Spryker\Client\SymfonyMessenger\Stamp\StampStackBuilderInterface;
use Spryker\Client\SymfonyMessenger\Transport\Amqp\AmqpTransportFactory;
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
        return new QueueSender($this->createMessageBusBuilder(), $this->getConfig(), $this->createQueueStampStackBuilder());
    }

    public function createQueueReceiver(): ReceiverInterface
    {
        return new QueueReceiver(
            $this->createDefaultQueueTransport(),
            $this->createSerializer(),
        );
    }

    public function createMessageBusBuilder(): MessageBusBuilderInterface
    {
        return new QueueMessageBusBuilder($this->createBusLocatorContainer());
    }

    public function createBusLocatorContainer(): ContainerInterface
    {
        return new BusLocatorContainer($this->getConfig(), $this->createSenderLocatorBuilder());
    }

    public function createSenderLocatorBuilder(): SenderLocatorBuilderInterface
    {
        return new SenderLocatorBuilder($this->getConfig(), $this->getAvailableTransports());
    }

    /**
     * Callable parameters array $options is transport options array. See Symfony Messenger documentation for details or specific transport factory.
     * Transport stored in the closure in order to delay its creation until it is actually needed.
     *
     * @return array<string, \Closure>
     */
    public function getAvailableTransports(): array
    {
        return [
            $this->getConfig()::TRANSPORT_AMQP => function (array $options = []): TransportInterface {
                return $this->createTransport($this->getConfig()->getQueueMessengerDSN(), $options);
            },
        ];
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
        return [
            new AmqpTransportFactory(),
        ];
    }

    public function createSerializer(): SerializerInterface
    {
        return new Serializer();
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
                $this->getConfig()->getQueueMessengerDSN(),
                $this->getConfig()->getQueueTransportConfiguration()['default'] ?? [],
            );
        }

        return static::$defaultQueueTransport;
    }
}
