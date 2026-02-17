<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\MessageBus\Container;

use Psr\Container\ContainerInterface;
use Spryker\Client\SymfonyMessenger\Sender\SenderLocatorBuilderInterface;
use Spryker\Client\SymfonyMessenger\SymfonyMessengerConfig;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;

class DefaultBusLocatorContainer implements ContainerInterface
{
    /**
     * @var array<string, \Closure|\Symfony\Component\Messenger\MessageBusInterface>
     */
    protected array $services = [];

    /**
     * @param array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface> $messageMapProviderPlugins
     */
    public function __construct(
        protected SymfonyMessengerConfig $config,
        protected SenderLocatorBuilderInterface $sendersLocatorBuilder,
        protected array $messageMapProviderPlugins = [],
    ) {
        $options = $this->buildBusOptions();
        foreach ($options as $option) {
            $this->services[sprintf('%s', $option['serviceKey'])] = function () use ($option) {
                return new MessageBus($this->buildMessageBusMiddlewareStack(array_merge($option, ['connection_name' => $option['serviceKey']])));
            };
        }
        $this->services['default'] = function () {
            return new MessageBus([
                $this->buildHandleMessageMiddleware(),
            ]);
        };
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
            $this->buildHandleMessageMiddleware($options),
        ];
    }

    /**
     * @return array<array<string, mixed>>
     */
    protected function buildBusOptions(): array
    {
        $options = [];
        $messageToTransportMap = $this->mapMessageToTransport();
        $transportConfig = $this->config->getTransportConfig();

        foreach ($messageToTransportMap as $transports) {
            foreach ($transports as $transport) {
                $options[] = [
                    'serviceKey' => $transport,
                    'transport_key_suffix' => $transport,
                    'options' => array_merge($transportConfig[$transport] ?? [], $transportConfig['default'] ?? []),
                ];
            }
        }

        return $options;
    }

    /**
     * @return array<string, array<string>>
     */
    protected function mapMessageToTransport(): array
    {
        $map = $this->config->getMessageToTransportMap();

        foreach ($this->messageMapProviderPlugins as $messageMapProviderPlugin) {
            foreach ($messageMapProviderPlugin->getMessageToTransportMap() as $message => $transport) {
                $transports = is_array($transport) ? $transport : [$transport];
                $map[$message] = $transports;
            }
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function buildSendMessageMiddleware(array $options = []): MiddlewareInterface
    {
        return new SendMessageMiddleware(
            $this->sendersLocatorBuilder->build($options),
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function buildHandleMessageMiddleware(array $options = []): MiddlewareInterface
    {
        $handlers = [];
        foreach ($this->messageMapProviderPlugins as $messageMapProviderPlugin) {
            $handlers = array_merge($handlers, $messageMapProviderPlugin->getMessageToHandlerMap());
        }

        return new HandleMessageMiddleware(
            new HandlersLocator($handlers),
            true,
        );
    }
}
