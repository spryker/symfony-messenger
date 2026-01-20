<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Shared\SymfonyMessenger;

/**
 * Declares global environment configuration keys. Do not use it for other class constants.
 */
interface SymfonyMessengerConstants
{
    /**
     * Specification:
     * - DSN for the queue messenger transport.
     * - Required format is protocol://user:pass@host:port/virtual-host. e.g. amqp://guest:guest@localhost:5672/eu-docker
     * - Protocols supported OOTB are `amqp` and `amqps`. For other protocols, custom transport factories must be implemented or existing Symfony transport packages can be installed.
     *
     * @api
     */
    public const string QUEUE_DSN = 'SYMFONY_MESSENGER:QUEUE_DSN';
}
