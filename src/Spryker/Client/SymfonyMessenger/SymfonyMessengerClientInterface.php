<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger;

use Generated\Shared\Transfer\QueueInformationCollectionTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Client\Queue\Model\Adapter\AdapterInterface;

interface SymfonyMessengerClientInterface
{
    /**
     * Specification:
     * - Sends a message to the specified queue.
     * - The QueueSendMessageTransfer is Transformed into the {@link \Spryker\Client\SymfonyMessenger\Messages\QueueMessage} OOTB.
     * - Each queue is defined as separate transport.
     * - Default configuration allows to create queues on the fly.
     *
     * @api
     *
     * @param string $queueName
     * @param \Generated\Shared\Transfer\QueueSendMessageTransfer $queueSendMessageTransfer
     *
     * @return void
     */
    public function sendMessageToQueue(string $queueName, QueueSendMessageTransfer $queueSendMessageTransfer): void;

    /**
     * Specification:
     * - Creates and returns a queue adapter that will be used for Queue module.
     *
     * @api
     *
     * @return \Spryker\Client\Queue\Model\Adapter\AdapterInterface
     */
    public function createQueueAdapter(): AdapterInterface;

    /**
     * Specification:
     * - Checks if at least one of provided queues has messages in it.
     *
     * @api
     *
     * @param array<string> $queueNames
     *
     * @return bool
     */
    public function areQueuesEmpty(array $queueNames): bool;

    /**
     * Specification:
     * - Sends a message using the Symfony Messenger component.
     *
     * @api
     *
     * @param object $message
     *
     * @return void
     */
    public function sendMessage(object $message): void;

    /**
     * Specification:
     * - Returns a collection of queues with their message counts.
     *
     * @api
     *
     * @param array<string> $queueNames
     *
     * @return \Generated\Shared\Transfer\QueueInformationCollectionTransfer
     */
    public function getQueues(array $queueNames): QueueInformationCollectionTransfer;

    /**
     * Specification:
     * - Consume messages using Symfony Messenger Worker.
     *
     * @api
     *
     * @param array<string> $receivers
     * @param array<string, mixed> $workerOptions
     *
     * @return int
     */
    public function consume(array $receivers, array $workerOptions): int;
}
