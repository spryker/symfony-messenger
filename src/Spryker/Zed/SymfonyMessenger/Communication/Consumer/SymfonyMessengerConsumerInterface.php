<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication\Consumer;

interface SymfonyMessengerConsumerInterface
{
    /**
     * @param array<string> $receivers
     * @param array<string, mixed> $options
     *
     * @return int
     */
    public function consume(array $receivers, array $options = []): int;
}
