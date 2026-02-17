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
     */
    public function __construct(
        protected MessageBusBuilderInterface $messageBusBuilder,
        protected array $availableTransports
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

        return new Worker($receiversWithTransports, $this->messageBusBuilder->getMessageBus());
    }
}
