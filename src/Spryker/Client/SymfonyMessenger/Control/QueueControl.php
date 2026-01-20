<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Control;

use Spryker\Client\SymfonyMessenger\Transport\QueueManagementTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class QueueControl implements QueueControlInterface
{
    public function __construct(protected TransportInterface $transport)
    {
    }

    /**
     * @return array<int, mixed>
     */
    public function createQueue(string $queueName): array
    {
        if (!($this->transport instanceof QueueManagementTransportInterface)) {
            return [];
        }

        return $this->transport->createQueue($queueName);
    }

    public function purgeQueue(string $queueName): bool
    {
        if (!($this->transport instanceof QueueManagementTransportInterface)) {
            return true;
        }

        return $this->transport->purgeQueue($queueName);
    }

    public function deleteQueue(string $queueName): bool
    {
        if (!($this->transport instanceof QueueManagementTransportInterface)) {
            return true;
        }

        return $this->transport->deleteQueue($queueName);
    }
}
