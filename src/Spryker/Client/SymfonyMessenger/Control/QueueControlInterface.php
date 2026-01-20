<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Control;

interface QueueControlInterface
{
    /**
     * @return array<int, string>
     */
    public function createQueue(string $queueName): array;

    public function purgeQueue(string $queueName): bool;

    public function deleteQueue(string $queueName): bool;
}
