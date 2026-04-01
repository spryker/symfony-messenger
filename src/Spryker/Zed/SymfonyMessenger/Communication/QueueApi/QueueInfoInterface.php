<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication\QueueApi;

use Generated\Shared\Transfer\QueueInformationCollectionTransfer;

interface QueueInfoInterface
{
    /**
     * @param array<string> $queueNames
     *
     * @return bool
     */
    public function areQueuesEmpty(array $queueNames): bool;

    /**
     * @param array<string> $queueNames
     *
     * @return \Generated\Shared\Transfer\QueueInformationCollectionTransfer
     */
    public function getQueues(array $queueNames): QueueInformationCollectionTransfer;
}
