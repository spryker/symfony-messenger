<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Business;

use Spryker\Client\SymfonyMessenger\SymfonyMessengerClientInterface;
use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;
use Spryker\Zed\SymfonyMessenger\Business\Queue\QueueMetricsReader;
use Spryker\Zed\SymfonyMessenger\Business\Queue\QueueMetricsReaderInterface;
use Spryker\Zed\SymfonyMessenger\SymfonyMessengerDependencyProvider;

/**
 * @method \Spryker\Zed\SymfonyMessenger\SymfonyMessengerConfig getConfig()
 */
class SymfonyMessengerBusinessFactory extends AbstractBusinessFactory
{
    public function createQueueMetricsReader(): QueueMetricsReaderInterface
    {
        return new QueueMetricsReader($this->getSymfonyMessengerClient());
    }

    public function getSymfonyMessengerClient(): SymfonyMessengerClientInterface
    {
        return $this->getProvidedDependency(SymfonyMessengerDependencyProvider::CLIENT_SYMFONY_MESSENGER);
    }
}
