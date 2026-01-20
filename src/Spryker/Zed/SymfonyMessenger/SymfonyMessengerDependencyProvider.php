<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

class SymfonyMessengerDependencyProvider extends AbstractBundleDependencyProvider
{
    public const string CLIENT_SYMFONY_MESSENGER = 'CLIENT_SYMFONY_MESSENGER';

    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addSymfonyMessengerClient($container);

        return $container;
    }

    protected function addSymfonyMessengerClient(Container $container): Container
    {
        $container->set(static::CLIENT_SYMFONY_MESSENGER, function (Container $container) {
            return $container->getLocator()->symfonyMessenger()->client();
        });

        return $container;
    }
}
