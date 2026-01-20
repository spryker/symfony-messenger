<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Sender;

use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

interface SenderLocatorBuilderInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function build(array $options = []): SendersLocatorInterface;
}
