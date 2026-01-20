<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Stamp;

interface StampStackBuilderInterface
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array<\Symfony\Component\Messenger\Stamp\StampInterface>
     */
    public function buildStack(array $options = []): array;
}
