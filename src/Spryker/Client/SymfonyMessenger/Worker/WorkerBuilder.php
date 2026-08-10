<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Worker;

use Spryker\Client\SymfonyMessenger\MessageBus\MessageBusBuilderInterface;

class WorkerBuilder implements WorkerBuilderInterface
{
    /**
     * @param array<string, callable> $availableTransports
     * @param array<string, int> $transportPriorities
     * @param array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\TransportConsumeGuardPluginInterface> $transportConsumeGuardPlugins
     */
    public function __construct(
        protected MessageBusBuilderInterface $messageBusBuilder,
        protected array $availableTransports,
        protected array $transportPriorities = [],
        protected array $transportConsumeGuardPlugins = []
    ) {
    }

    /**
     * @param array<string> $receivers
     * @param array<string, mixed> $options
     */
    public function build(array $receivers, array $options = []): Worker
    {
        $receiversWithTransports = [];
        foreach ($this->availableTransports as $transportName => $availableTransport) {
            if (in_array($transportName, $receivers, true)) {
                $receiversWithTransports[$transportName] = $availableTransport($options);
            }
        }

        $receiversWithTransports = $this->sortByPriority($receiversWithTransports);

        return new Worker(
            $receiversWithTransports,
            $this->messageBusBuilder->getMessageBus(),
            transportConsumeGuardPlugins: $this->transportConsumeGuardPlugins,
        );
    }

    /**
     * @param array<string, \Symfony\Component\Messenger\Transport\TransportInterface> $receiversWithTransports
     *
     * @return array<string, \Symfony\Component\Messenger\Transport\TransportInterface>
     */
    protected function sortByPriority(array $receiversWithTransports): array
    {
        // The higher the priority, the earlier the transport is polled by the worker.
        uksort($receiversWithTransports, function (string $transportNameA, string $transportNameB): int {
            return ($this->transportPriorities[$transportNameB] ?? 0) <=> ($this->transportPriorities[$transportNameA] ?? 0);
        });

        return $receiversWithTransports;
    }
}
