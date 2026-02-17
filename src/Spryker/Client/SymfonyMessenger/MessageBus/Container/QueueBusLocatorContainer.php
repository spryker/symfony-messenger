<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\MessageBus\Container;

use Psr\Container\ContainerInterface;
use Spryker\Client\SymfonyMessenger\Sender\SenderLocatorBuilderInterface;
use Spryker\Client\SymfonyMessenger\SymfonyMessengerConfig;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;

/**
 * This container will be used in the RoutableMessageBus to locate different buses by queue(exchange) name.
 * For each configured queue(exchange) a separate bus will be created with its own SendMessageMiddleware that is configured to work with that specific queue(exchange).
 * OOTB SendMessageMiddleware is used here to send messages to the specific queue(exchange) using the appropriate sender. Additional middlewares can be added by extending the buildMessageBusMiddlewareStack() method.
 * buildBusOptions() method reads the queue configurations from SymfonyMessengerConfig and prepares queue exchange binding for SendMessageMiddleware middlewares. It can be extended if additional options are needed for other middlewares.
 * By default, each exchange will have an associated error queue with the ".error" suffix. If configuration has also other binding that will be added on top of that.
 * Each bus is lazily instantiated when requested for the first time via the get() method.
 */
class QueueBusLocatorContainer implements ContainerInterface
{
    /**
     * @var array<string, \Closure|\Symfony\Component\Messenger\MessageBusInterface>
     */
    protected array $services = [];

    public function __construct(
        protected SymfonyMessengerConfig $config,
        protected SenderLocatorBuilderInterface $sendersLocatorBuilder
    ) {
        $options = $this->buildBusOptions();
        foreach ($options as $option) {
            $this->services[sprintf('%s', $option['exchange_name'])] = function () use ($option) {
                return new MessageBus($this->buildMessageBusMiddlewareStack(array_merge($option, ['connection_name' => $option['exchange_name']])));
            };
        }
    }

    public function get(string $id): MessageBusInterface
    {
        if (!($this->services[$id] instanceof MessageBusInterface)) {
            $this->services[$id] = $this->services[$id]();
        }

        /** @var \Symfony\Component\Messenger\MessageBusInterface $service */
        $service = $this->services[$id];

        return $service;
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<\Symfony\Component\Messenger\Middleware\MiddlewareInterface>
     */
    protected function buildMessageBusMiddlewareStack(array $options = []): array
    {
        return [
            $this->buildSendMessageMiddleware($options),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function buildBusOptions(): array
    {
        $options = [];
        $genericQueueConfig = $this->config->getQueueTransportConfiguration();

        foreach ($this->config->getQueueConfiguration() as $queue => $queueData) {
            /** @var string $queueName */
            $queueName = $queueData;
            $bindData = null;
            if (is_array($queueData)) {
                /** @var string $queueName */
                $queueName = $queue;
                $bindData = $queueData;
            }
            $queues = [
                $queueName => [],
                $queueName . '.error' => [
                    'binding_keys' => ['error'],
                ],
            ];
            if ($bindData) {
                foreach ($bindData as $bindingKey => $bindQueueName) {
                    $queues[$bindQueueName] = [
                        'binding_keys' => [
                            $bindingKey,
                        ],
                    ];
                }
            }
            $options[$queueName] = [
                'queues' => $queues,
                'exchange_name' => $queueName,
            ];
            if (isset($genericQueueConfig[$queueName]) || isset($genericQueueConfig['default'])) {
                $config = $genericQueueConfig[$queueName] ?? $genericQueueConfig['default'];
                $options[$queueName] = array_merge($options[$queueName], $config);
            }
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function buildSendMessageMiddleware(array $options = []): MiddlewareInterface
    {
        $exchangeName = $options['exchange_name'];
        $queueBindings = $options['queues'] ?? [];
        unset($options['exchange_name']);
        unset($options['queues']);

        $compiledOptions = array_merge($options, [
            'queues' => $queueBindings,
            'exchange' => [
                'name' => $exchangeName,
                'type' => 'direct',
            ],
        ]);

        $compiledOptions['transport_key_suffix'] = $compiledOptions['exchange']['name'];

        return new SendMessageMiddleware(
            $this->sendersLocatorBuilder->build($compiledOptions),
        );
    }
}
