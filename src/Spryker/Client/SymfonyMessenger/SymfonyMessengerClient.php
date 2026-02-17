<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger;

use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Client\Kernel\AbstractClient;
use Spryker\Client\Queue\Model\Adapter\AdapterInterface;

/**
 * @method \Spryker\Client\SymfonyMessenger\SymfonyMessengerFactory getFactory()
 */
class SymfonyMessengerClient extends AbstractClient implements SymfonyMessengerClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param string $queueName
     * @param \Generated\Shared\Transfer\QueueSendMessageTransfer $queueSendMessageTransfer
     *
     * @return void
     */
    public function sendMessageToQueue(string $queueName, QueueSendMessageTransfer $queueSendMessageTransfer): void
    {
        $this->getFactory()->createQueueSender()->sendMessage($queueName, $queueSendMessageTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return \Spryker\Client\Queue\Model\Adapter\AdapterInterface
     */
    public function createQueueAdapter(): AdapterInterface
    {
        return $this->getFactory()->createQueueAdapter();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<string> $queueNames
     *
     * @return bool
     */
    public function areQueuesEmpty(array $queueNames): bool
    {
        /** @var \Spryker\Client\SymfonyMessenger\Transport\QueueManagementTransportInterface $transport */
        $transport = $this->getFactory()->createDefaultQueueTransport();

        return $transport->areQueuesEmpty($queueNames);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param object $message
     *
     * @return void
     */
    public function sendMessage(object $message): void
    {
        $this->getFactory()->createSender()->send($message);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @param array<string> $receivers
     * @param array<string, mixed> $workerOptions
     *
     * @return int
     */
    public function consume(array $receivers, array $workerOptions): int
    {
        return $this->getFactory()->createConsumer()->consume($receivers, $workerOptions);
    }
}
