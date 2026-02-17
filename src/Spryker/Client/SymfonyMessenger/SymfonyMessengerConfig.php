<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger;

use Spryker\Client\Kernel\AbstractBundleConfig;
use Spryker\Client\SymfonyMessenger\Messages\QueueMessage;
use Spryker\Shared\SymfonyMessenger\SymfonyMessengerConstants;

class SymfonyMessengerConfig extends AbstractBundleConfig
{
    public const string TRANSPORT_AMQP = 'amqp';

    /**
     * Specification:
     * - Returns DSN for queue messenger transport.
     *
     * @api
     */
    public function getQueueMessengerDSN(): ?string
    {
        return $this->get(SymfonyMessengerConstants::QUEUE_DSN);
    }

    /**
     * Specification:
     * - Returns mapping of message class to transport names.
     *
     * @api
     *
     * @deprecated Map message to transports by adding implementation of {@link \Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\MessageMappingProviderPluginInterface} instead.
     *
     * @return array<string, array<int, string>>
     */
    public function getMessageToTransportMap(): array
    {
        return [
            QueueMessage::class => [
                static::TRANSPORT_AMQP,
            ],
        ];
    }

    /**
     * Specification:
     * - Returns transport configuration for messenger transports.
     * - Each key is a transport name, each value is an array of transport options.
     * - `default` key is used for default transport configuration.
     *
     * @api
     *
     * @return array<string, array<string, mixed>>
     */
    public function getTransportConfig(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns list of existing queues.
     * - Each item Can be just a name of the queue or an string -> array where string is a queue name and array is routing binding.
     *
     * @api
     *
     * @return array<string|int, string|array<string, string>>
     */
    public function getQueueConfiguration(): array
    {
        return [];
    }

    /**
     * Specification:
     * - Returns transport configuration for queue transport.
     * - Each key is a queue name, each value is an array of transport options.
     * - `default` key is used for default transport configuration.
     *
     * @api
     *
     * @return array<string, array<string, mixed>>
     */
    public function getQueueTransportConfiguration(): array
    {
        return [
            'default' => [
                'auto_setup' => false,
                'persistent' => 'true',
                'connect_timeout' => 3,
                'read_timeout' => 130,
                'write_timeout' => 130,
                'heartbeat' => 0,
                'rpc_timeout' => 0,
            ],
        ];
    }
}
