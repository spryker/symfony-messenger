<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\MessageBus;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\RoutableMessageBus;

class QueueMessageBusBuilder implements MessageBusBuilderInterface
{
    protected static ?ContainerInterface $container = null;

    public function __construct(ContainerInterface $busLocatorContainer)
    {
        static::$container = $busLocatorContainer;
    }

    public function getMessageBus(): MessageBusInterface
    {
        return $this->getRouterMessageBus();
    }

    protected function getRouterMessageBus(): MessageBusInterface
    {
        return new RoutableMessageBus(static::$container);
    }
}
