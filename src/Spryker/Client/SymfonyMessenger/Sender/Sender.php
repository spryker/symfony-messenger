<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Sender;

use RuntimeException;
use Spryker\Client\SymfonyMessenger\MessageBus\MessageBusBuilderInterface;
use Spryker\Client\SymfonyMessenger\SymfonyMessengerConfig;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

class Sender implements SenderInterface
{
    /**
     * @var array<string, array<string>>
     */
    protected static array $messageMap = [];

    /**
     * @param array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface> $messageMapProviderPlugins
     */
    public function __construct(
        protected MessageBusBuilderInterface $messageBusBuilder,
        protected SymfonyMessengerConfig $messengerConfig,
        protected array $messageMapProviderPlugins = [],
    ) {
    }

    /**
     * @param array<\Symfony\Component\Messenger\Stamp\StampInterface> $stamps
     *
     * @throws \RuntimeException
     */
    public function send(object $message, array $stamps = []): void
    {
        $transportNames = $this->getTransportByMessage($message);
        if ($transportNames === null) {
            throw new RuntimeException(sprintf('No transport found for message of class %s. Please provide it via \Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface plugin implementation', get_class($message)));
        }

        $messageBus = $this->messageBusBuilder->getMessageBus();
        foreach ($transportNames as $transportName) {
            $envelope = $this->buildEnvelope($message, array_merge($stamps, [new BusNameStamp($transportName)]));
            $messageBus->dispatch($envelope);
        }
    }

    /**
     * @param object $message
     *
     * @return array<string>|null
     */
    protected function getTransportByMessage(object $message): array|null
    {
        if (static::$messageMap === []) {
            static::$messageMap = $this->messengerConfig->getMessageToTransportMap();
            foreach ($this->messageMapProviderPlugins as $messageMapProviderPlugin) {
                foreach ($messageMapProviderPlugin->getMessageToTransportMap() as $messageClass => $transport) {
                    $transportNames = is_array($transport) ? $transport : [$transport];
                    static::$messageMap[$messageClass] = $transportNames;
                }
            }
        }

        $messageClass = get_class($message);
        if (isset(static::$messageMap[$messageClass])) {
            return static::$messageMap[$messageClass];
        }

        return null;
    }

    /**
     * @param array<\Symfony\Component\Messenger\Stamp\StampInterface> $stamps
     */
    protected function buildEnvelope(object $message, array $stamps = []): Envelope
    {
        return new Envelope($message, $stamps);
    }
}
