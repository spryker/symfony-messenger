<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Adapter;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Client\SymfonyMessenger\Control\QueueControlInterface;
use Spryker\Client\SymfonyMessenger\Receiver\ReceiverInterface;
use Spryker\Client\SymfonyMessenger\Sender\QueueSenderInterface;

class SymfonyMessengerQueueAdapter implements SymfonyMessengerQueueAdapterInterface
{
    public function __construct(
        protected QueueSenderInterface $queueSender,
        protected ReceiverInterface $receiver,
        protected QueueControlInterface $queueControl,
    ) {
    }

    /**
     * @param string $queueName
     * @param array<string, mixed> $options
     *
     * @return array<int, mixed>
     */
    public function createQueue($queueName, array $options = []): array
    {
        return $this->queueControl->createQueue($queueName);
    }

    /**
     * @param string $queueName
     * @param array<string, mixed> $options
     */
    public function purgeQueue($queueName, array $options = []): bool
    {
        return $this->queueControl->purgeQueue($queueName);
    }

    /**
     * @param string $queueName
     * @param array<string, mixed> $options
     */
    public function deleteQueue($queueName, array $options = []): bool
    {
        return $this->queueControl->deleteQueue($queueName);
    }

    /**
     * @param string $queueName
     * @param array<string, mixed> $options
     */
    public function receiveMessage($queueName, array $options = []): QueueReceiveMessageTransfer
    {
        return $this->receiver->receiveMessage($queueName, $options);
    }

    public function handleError(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): bool
    {
        $this->queueSender->handleError($queueReceiveMessageTransfer);

        return true;
    }

    public function sendMessage($queueName, QueueSendMessageTransfer $queueSendMessageTransfer): void
    {
        $this->queueSender->sendMessage($queueName, $queueSendMessageTransfer);
    }

    /**
     * @param string $queueName
     * @param array<\Generated\Shared\Transfer\QueueSendMessageTransfer> $queueSendMessageTransfers
     */
    public function sendMessages($queueName, array $queueSendMessageTransfers): void
    {
        $this->queueSender->sendMessages($queueName, $queueSendMessageTransfers);
    }

    /**
     * @param string $queueName
     * @param array<string, mixed> $options
     */
    public function receiveMessages($queueName, $chunkSize = 100, array $options = []): array
    {
        return $this->receiver->receiveMessages($queueName, $chunkSize, $options);
    }

    public function acknowledge(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void
    {
        $this->receiver->acknowledgeFromQueueReceiveMessageTransfer($queueReceiveMessageTransfer);
    }

    public function reject(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void
    {
        $this->receiver->rejectFromQueueReceiveMessageTransfer($queueReceiveMessageTransfer);
    }
}
