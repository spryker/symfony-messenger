<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication\QueueApi;

use Generated\Shared\Transfer\QueueInformationCollectionTransfer;
use Spryker\Client\SymfonyMessenger\SymfonyMessengerClientInterface;

class QueueInfo implements QueueInfoInterface
{
    public function __construct(
        protected SymfonyMessengerClientInterface $client,
    ) {
    }

    /**
     * @param array<string> $queueNames
     *
     * @return bool
     */
    public function areQueuesEmpty(array $queueNames): bool
    {
        return $this->client->areQueuesEmpty($queueNames);
    }

    /**
     * @param array<string> $queueNames
     *
     * @return \Generated\Shared\Transfer\QueueInformationCollectionTransfer
     */
    public function getQueues(array $queueNames): QueueInformationCollectionTransfer
    {
        return $this->client->getQueues($queueNames);
    }
}
