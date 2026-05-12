<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Sender;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Client\SymfonyMessenger\MessageBus\MessageBusBuilderInterface;
use Spryker\Client\SymfonyMessenger\Messages\QueueMessage;
use Spryker\Client\SymfonyMessenger\Stamp\StampStackBuilderInterface;
use Spryker\Client\SymfonyMessenger\SymfonyMessengerConfig;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class QueueSender implements QueueSenderInterface
{
    /**
     * Message bus is static as it's not going to change during the request lifecycle and by making it static we avoid building it multiple times in case of multiple sender instances.
     */
    protected static ?MessageBusInterface $routerMessageBus = null;

    public function __construct(
        protected MessageBusBuilderInterface $messageBusBuilder,
        protected SymfonyMessengerConfig $messengerConfig,
        protected StampStackBuilderInterface $stampStackBuilder,
    ) {
    }

    public function sendMessage(string $queueName, QueueSendMessageTransfer $queueSendMessageTransfer): void
    {
        $queueName = $this->cleanUpRetry($queueName);
        $options = [];
        if ($queueSendMessageTransfer->getRoutingKey()) {
            $options = [
                'queue_options' => [
                    'routing_key' => $queueSendMessageTransfer->getRoutingKey(),
                ],
            ];
        }
        $this->getRouterMessageBus()
            ->dispatch(
                $this->buildEnvelop(
                    $queueSendMessageTransfer->getBody(),
                    $queueSendMessageTransfer->getHeaders(),
                    $queueName,
                    $options,
                ),
            );
    }

    /**
     * @param array<\Generated\Shared\Transfer\QueueSendMessageTransfer> $queueSendMessageTransfers
     */
    public function sendMessages(string $queueName, array $queueSendMessageTransfers): void
    {
        $queueName = $this->cleanUpRetry($queueName);
        foreach ($queueSendMessageTransfers as $queueSendMessageTransfer) {
            $options = [];
            if ($queueSendMessageTransfer->getRoutingKey()) {
                $options = [
                    'queue_options' => [
                        'routing_key' => $queueSendMessageTransfer->getRoutingKey(),
                    ],
                ];
            }
            $this->getRouterMessageBus()->dispatch(
                $this->buildEnvelop(
                    $queueSendMessageTransfer->getBody(),
                    $queueSendMessageTransfer->getHeaders(),
                    $queueName,
                    $options,
                ),
            );
        }
    }

    public function handleError(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void
    {
        $queueReceiveMessageTransfer->setQueueName($this->cleanUpRetry($queueReceiveMessageTransfer->getQueueName()));
        $this->getRouterMessageBus()
            ->dispatch(
                $this->buildEnvelop(
                    $queueReceiveMessageTransfer->getQueueMessage()->getBody(),
                    $queueReceiveMessageTransfer->getQueueMessage()->getHeaders(),
                    $queueReceiveMessageTransfer->getQueueName(),
                    [
                        'queue_options' => [
                            'routing_key' => $queueReceiveMessageTransfer->getRoutingKey(),
                        ],
                    ],
                ),
            );
    }

    protected function getRouterMessageBus(): MessageBusInterface
    {
        if (static::$routerMessageBus === null) {
            static::$routerMessageBus = $this->messageBusBuilder->getMessageBus();
        }

        return static::$routerMessageBus;
    }

    /**
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $options
     */
    protected function buildEnvelop(string $body, array $headers, string $queueName, array $options = []): Envelope
    {
        return new Envelope(
            $this->buildMessage($body),
            $this->stampStackBuilder->buildStack(array_merge(['queue_name' => $queueName, 'headers' => $headers], $options)),
        );
    }

    protected function buildMessage(string $body): QueueMessage
    {
        return (new QueueMessage())->setBody($body);
    }

    protected function cleanUpRetry(string $queueName): string
    {
        if (str_ends_with($queueName, '.retry')) {
            $queueName = str_replace('.retry', '', $queueName);
        }

        return $queueName;
    }
}
