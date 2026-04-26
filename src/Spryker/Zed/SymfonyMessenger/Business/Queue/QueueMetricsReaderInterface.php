<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Business\Queue;

use Generated\Shared\Transfer\QueueMetricsRequestTransfer;
use Generated\Shared\Transfer\QueueMetricsResponseTransfer;

interface QueueMetricsReaderInterface
{
    public function read(QueueMetricsRequestTransfer $queueMetricsRequestTransfer): QueueMetricsResponseTransfer;
}
