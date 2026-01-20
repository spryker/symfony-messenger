<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger;

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
}
