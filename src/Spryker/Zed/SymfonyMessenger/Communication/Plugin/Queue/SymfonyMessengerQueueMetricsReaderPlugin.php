<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication\Plugin\Queue;

use Generated\Shared\Transfer\QueueMetricsRequestTransfer;
use Generated\Shared\Transfer\QueueMetricsResponseTransfer;
use Spryker\Client\SymfonyMessenger\Adapter\SymfonyMessengerQueueAdapter;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\QueueExtension\Dependency\Plugin\QueueMetricsReaderPluginInterface;

/**
 * @method \Spryker\Zed\SymfonyMessenger\Business\SymfonyMessengerFacadeInterface getFacade()
 * @method \Spryker\Zed\SymfonyMessenger\SymfonyMessengerConfig getConfig()
 * @method \Spryker\Zed\SymfonyMessenger\Communication\SymfonyMessengerCommunicationFactory getFactory()
 * @method \Spryker\Zed\SymfonyMessenger\Business\SymfonyMessengerBusinessFactory getBusinessFactory()
 */
class SymfonyMessengerQueueMetricsReaderPlugin extends AbstractPlugin implements QueueMetricsReaderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Reads queue metrics for the SymfonyMessenger adapter.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\QueueMetricsRequestTransfer $queueMetricsRequestTransfer
     *
     * @return \Generated\Shared\Transfer\QueueMetricsResponseTransfer
     */
    public function read(
        QueueMetricsRequestTransfer $queueMetricsRequestTransfer,
    ): QueueMetricsResponseTransfer {
        return $this->getBusinessFactory()->createQueueMetricsReader()->read($queueMetricsRequestTransfer);
    }

    /**
     * {@inheritDoc}
     * - Returns true if the adapter class name matches SymfonyMessengerQueueAdapter.
     *
     * @api
     *
     * @param string $adapterClassName
     *
     * @return bool
     */
    public function isApplicable(string $adapterClassName): bool
    {
        return $adapterClassName === SymfonyMessengerQueueAdapter::class;
    }
}
