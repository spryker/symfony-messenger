<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Consumer;

use Spryker\Client\SymfonyMessenger\Worker\ErrorAwareWorkerInterface;
use Spryker\Client\SymfonyMessenger\Worker\WorkerBuilderInterface;

class Consumer implements ConsumerInterface
{
    protected const int CODE_SUCCESS = 0;

    protected const int CODE_ERROR = 1;

    /**
     * @param array<\Spryker\Shared\SymfonyMessengerExtension\Dependency\Plugin\GroupAwareTransportsPluginInterface> $groupAwareTransportsPlugins
     */
    public function __construct(protected WorkerBuilderInterface $workerBuilder, protected array $groupAwareTransportsPlugins = [])
    {
    }

    /**
     * @param array<string> $receivers
     * @param array<string, mixed> $options
     *
     * @return int
     */
    public function consume(array $receivers, array $options = []): int
    {
        $receivers = $this->filterReceivers($receivers, $options);
        $worker = $this->workerBuilder->build($receivers, $options);

        $worker->run($options);

        return ($worker instanceof ErrorAwareWorkerInterface && $worker->hadErrors())
            ? static::CODE_ERROR
            : static::CODE_SUCCESS;
    }

    /**
     * @param array<string> $receivers
     * @param array<string, mixed> $options
     *
     * @return array<string>
     */
    protected function filterReceivers(array $receivers, array $options = []): array
    {
        $filteredReceivers = [];
        $groups = [];
        foreach ($this->groupAwareTransportsPlugins as $groupAwareTransportsPlugin) {
            $groups = array_merge($groups, $groupAwareTransportsPlugin->getGroupMapping());
        }

        foreach ($receivers as $receiver) {
            if (!in_array($receiver, array_keys($groups), true)) {
                $filteredReceivers[] = $receiver;

                continue;
            }
            $groupedReceivers = $groups[$receiver];
            $filteredReceivers = array_merge($filteredReceivers, $groupedReceivers);
        }

        return array_diff($filteredReceivers, $options['exclude'] ?? []);
    }
}
