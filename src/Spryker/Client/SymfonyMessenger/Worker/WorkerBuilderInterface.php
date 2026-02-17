<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Worker;

use Symfony\Component\Messenger\Worker;

interface WorkerBuilderInterface
{
    /**
     * @param array<string> $receivers
     * @param array<string, mixed> $options
     */
    public function build(array $receivers, array $options = []): Worker;
}
