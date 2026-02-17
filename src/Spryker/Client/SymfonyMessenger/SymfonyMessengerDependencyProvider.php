<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;

class SymfonyMessengerDependencyProvider extends AbstractDependencyProvider
{
    public const string PLUGINS_TRANSPORT_FACTORY_PROVIDER = 'PLUGINS_TRANSPORT_FACTORY_PROVIDER';

    public const string PLUGINS_MESSAGE_MAPPING_PROVIDER = 'PLUGINS_MESSAGE_MAPPING_PROVIDER';

    public const string PLUGINS_AVAILABLE_TRANSPORT_PROVIDER = 'PLUGINS_AVAILABLE_TRANSPORT_PROVIDER';

    public const string PLUGINS_GROUP_AWARE_TRANSPORTS_PLUGIN = 'PLUGINS_GROUP_AWARE_TRANSPORTS_PLUGIN';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addTransportFactoryProviderPlugins($container);
        $container = $this->addMessageMappingProviderPlugins($container);
        $container = $this->addAvailableTransportProviderPlugins($container);
        $container = $this->addGroupAwareTransportsPlugins($container);

        return $container;
    }

    protected function addTransportFactoryProviderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_TRANSPORT_FACTORY_PROVIDER, function () {
            return $this->getTransportFactoryProviderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\TransportFactoryProviderPluginInterface>
     */
    protected function getTransportFactoryProviderPlugins(): array
    {
        return [];
    }

    protected function addMessageMappingProviderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_MESSAGE_MAPPING_PROVIDER, function () {
            return $this->getMessageMappingProviderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface>
     */
    protected function getMessageMappingProviderPlugins(): array
    {
        return [];
    }

    protected function addAvailableTransportProviderPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_AVAILABLE_TRANSPORT_PROVIDER, function () {
            return $this->getAvailableTransportProviderPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\AvailableTransportProviderPluginInterface>
     */
    protected function getAvailableTransportProviderPlugins(): array
    {
        return [];
    }

    protected function addGroupAwareTransportsPlugins(Container $container): Container
    {
        $container->set(static::PLUGINS_GROUP_AWARE_TRANSPORTS_PLUGIN, function () {
            return $this->getGroupAwareTransportsPlugins();
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\AvailableTransportProviderPluginInterface>
     */
    protected function getGroupAwareTransportsPlugins(): array
    {
        return [];
    }
}
