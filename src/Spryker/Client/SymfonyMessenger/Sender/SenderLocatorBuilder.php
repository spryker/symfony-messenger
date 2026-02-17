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
     * @param array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface> $messageMapProviderPlugins
     */
    public function __construct(
        protected SymfonyMessengerConfig $messengerConfig,
        protected array $availableTransports = [],
        protected array $messageMapProviderPlugins = [],
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function build(array $options = []): SendersLocatorInterface
    {
        return new SendersLocator(
            $this->mapMessageToTransport(),
            $this->buildSenderLocatorContainer($options),
        );
    }

    /**
     * @return array<string, array<string>>
     */
    protected function mapMessageToTransport(): array
    {
        $map = $this->messengerConfig->getMessageToTransportMap();

        foreach ($this->messageMapProviderPlugins as $messageMapProviderPlugin) {
            foreach ($messageMapProviderPlugin->getMessageToTransportMap() as $message => $transport) {
                $transport = is_array($transport) ? $transport : [$transport];
                $map[$message] = $transport;
            }
        }

        return $map;
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
            protected static array $transportPerName = [];

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
                $key = $this->options['exchange']['name'] ?? $this->options['transport_key_suffix'] ?? '';
                $key = $id . ':' . $key;
                unset($this->options['transport_key_suffix']);
                if (!isset(static::$transportPerName[$key])) {
                    static::$transportPerName[$key] = static::$factories[$id]($this->options);
                }

                return static::$transportPerName[$key];
            }
        };
    }
}
