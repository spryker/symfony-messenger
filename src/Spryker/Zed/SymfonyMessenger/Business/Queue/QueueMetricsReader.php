<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Business\Queue;

use Exception;
use Generated\Shared\Transfer\QueueMetricsRequestTransfer;
use Generated\Shared\Transfer\QueueMetricsResponseTransfer;
use Spryker\Client\SymfonyMessenger\SymfonyMessengerClientInterface;

class QueueMetricsReader implements QueueMetricsReaderInterface
{
    public function __construct(protected SymfonyMessengerClientInterface $client)
    {
    }

    public function read(QueueMetricsRequestTransfer $queueMetricsRequestTransfer): QueueMetricsResponseTransfer
    {
        $queueInformationCollectionTransfer = $this->client->getQueues([$queueMetricsRequestTransfer->getQueueName()]);
        foreach ($queueInformationCollectionTransfer->getQueues() as $queueInformationTransfer) {
            if ($queueInformationTransfer->getName() === $queueMetricsRequestTransfer->getQueueName()) {
                return (new QueueMetricsResponseTransfer())->setMessageCount($queueInformationTransfer->getReadyCount());
            }
        }

        throw new Exception(sprintf('Queue "%s" not found.', $queueMetricsRequestTransfer->getQueueName()));
    }
}
