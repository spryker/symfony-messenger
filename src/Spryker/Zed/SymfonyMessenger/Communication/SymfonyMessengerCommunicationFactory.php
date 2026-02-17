<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication;

use Spryker\Client\SymfonyMessenger\SymfonyMessengerClientInterface;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\SymfonyMessenger\Communication\Consumer\SymfonyMessengerConsumer;
use Spryker\Zed\SymfonyMessenger\Communication\Consumer\SymfonyMessengerConsumerInterface;
use Spryker\Zed\SymfonyMessenger\Communication\QueueApi\QueueInfo;
use Spryker\Zed\SymfonyMessenger\Communication\QueueApi\QueueInfoInterface;
use Spryker\Zed\SymfonyMessenger\SymfonyMessengerDependencyProvider;

/**
 * @method \Spryker\Zed\SymfonyMessenger\SymfonyMessengerConfig getConfig()
 */
class SymfonyMessengerCommunicationFactory extends AbstractCommunicationFactory
{
    public function createQueueInfo(): QueueInfoInterface
    {
        return new QueueInfo(
            $this->getSymfonyMessengerClient(),
        );
    }

    public function createSymfonyMessengerConsumer(): SymfonyMessengerConsumerInterface
    {
        return new SymfonyMessengerConsumer(
            $this->getSymfonyMessengerClient(),
        );
    }

    public function getSymfonyMessengerClient(): SymfonyMessengerClientInterface
    {
        return $this->getProvidedDependency(SymfonyMessengerDependencyProvider::CLIENT_SYMFONY_MESSENGER);
    }
}
