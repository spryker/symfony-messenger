<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Sender;

use Psr\Container\ContainerInterface;
use Spryker\Client\SymfonyMessenger\SymfonyMessengerConfig;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class SenderLocatorBuilder implements SenderLocatorBuilderInterface
{
    /**
     * @param array<string, \Closure> $availableTransports
     */
    public function __construct(
        protected SymfonyMessengerConfig $messengerConfig,
        protected array $availableTransports = []
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function build(array $options = []): SendersLocatorInterface
    {
        return new SendersLocator(
            $this->messengerConfig->getMessageToTransportMap(),
            $this->buildSenderLocatorContainer($options),
        );
    }

    /**
     * Sender locator container is used to resolve TransportFactory by the message class name
     *
     * @param array<string, mixed> $options
     */
    protected function buildSenderLocatorContainer(array $options = []): ContainerInterface
    {
        return new class ($this->availableTransports, $options) implements ContainerInterface {
            /**
             * @var array<string, callable>
             */
            protected static array $factories = [];

            /**
             * @var array<string, \Symfony\Component\Messenger\Transport\TransportInterface>
             */
            protected static array $transportPerExchange = [];

            /**
             * @param array<string, \Closure> $transports
             * @param array<string, mixed> $options
             */
            public function __construct(protected array $transports, protected array $options = [])
            {
                if (static::$factories) {
                    return;
                }

                static::$factories = $this->transports;
            }

            public function has(string $id): bool
            {
                return isset(static::$factories[$id]);
            }

            public function get(string $id): TransportInterface
            {
                $exchangeName = $this->options['exchange']['name'] ?? '';
                $key = $id . ':' . $exchangeName;
                if (!isset(static::$transportPerExchange[$key])) {
                    static::$transportPerExchange[$key] = static::$factories[$id]($this->options);
                }

                return static::$transportPerExchange[$key];
            }
        };
    }
}
