<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication\Plugin\Queue;

use Spryker\Client\SymfonyMessenger\Adapter\SymfonyMessengerQueueAdapter;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\QueueExtension\Dependency\Plugin\QueueMessageCheckerPluginInterface;

/**
 * @method \Spryker\Zed\SymfonyMessenger\Business\SymfonyMessengerFacadeInterface getFacade()
 * @method \Spryker\Zed\SymfonyMessenger\SymfonyMessengerConfig getConfig()
 * @method \Spryker\Zed\SymfonyMessenger\Communication\SymfonyMessengerCommunicationFactory getFactory()
 */
class SymfonyMessengerQueueMessageCheckerPlugin extends AbstractPlugin implements QueueMessageCheckerPluginInterface
{
    /**
     * {@inheritDoc}
     * - Checks if any of the queues provided have messages in them.
     *
     * @api
     *
     * @param array<string> $queueNames
     *
     * @return bool
     */
    public function areQueuesEmpty(array $queueNames): bool
    {
        return $this->getFactory()->createQueueInfo()->areQueuesEmpty($queueNames);
    }

    /**
     * {@inheritDoc}
     * - Checks if the current queue adapter is RabbitMq.
     *
     * @api
     *
     * @param string $adapterName
     *
     * @return bool
     */
    public function isApplicable(string $adapterName): bool
    {
        return $adapterName === SymfonyMessengerQueueAdapter::class;
    }
}
