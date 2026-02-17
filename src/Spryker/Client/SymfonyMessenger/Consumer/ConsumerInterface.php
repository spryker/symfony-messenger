<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Consumer;

interface ConsumerInterface
{
    /**
     * @param array<string> $receivers
     * @param array<string, mixed> $options
     *
     * @return int
     */
    public function consume(array $receivers, array $options = []): int;
}
