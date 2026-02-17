<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Sender;

interface SenderInterface
{
    /**
     * @param array<\Symfony\Component\Messenger\Stamp\StampInterface> $stamps
     */
    public function send(object $message, array $stamps = []): void;
}
