<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\MessageBus;

use Symfony\Component\Messenger\MessageBusInterface;

interface MessageBusBuilderInterface
{
    public function getMessageBus(): MessageBusInterface;
}
