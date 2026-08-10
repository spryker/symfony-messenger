<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication\Process;

interface ProcessPoolInterface
{
    /**
     * @param array<int, array<string>> $commands Where the key is the worker number and the value is the command as a list of arguments.
     * @param array<string, mixed> $options
     */
    public function run(array $commands, array $options = []): int;
}
