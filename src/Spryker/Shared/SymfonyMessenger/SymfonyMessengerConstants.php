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
     * - Credentials MUST be rawurlencode()-encoded when they contain special characters.
     * - Protocols supported OOTB are `amqp` and `amqps`. For other protocols, custom transport factories must be implemented or existing Symfony transport packages can be installed.
     * - Deprecated: use individual connection constants instead to avoid manual URL-encoding of credentials.
     *
     * @api
     */
    public const string QUEUE_DSN = 'SYMFONY_MESSENGER:QUEUE_DSN';

    /**
     * Specification:
     * - AMQP broker hostname.
     * - Used together with other QUEUE_AMQP_* constants to build the DSN automatically with proper URL-encoding.
     *
     * @api
     */
    public const string QUEUE_AMQP_HOST = 'SYMFONY_MESSENGER:QUEUE_AMQP_HOST';

    /**
     * Specification:
     * - AMQP broker port.
     *
     * @api
     */
    public const string QUEUE_AMQP_PORT = 'SYMFONY_MESSENGER:QUEUE_AMQP_PORT';

    /**
     * Specification:
     * - AMQP broker username. Special characters are handled automatically.
     *
     * @api
     */
    public const string QUEUE_AMQP_USERNAME = 'SYMFONY_MESSENGER:QUEUE_AMQP_USERNAME';

    /**
     * Specification:
     * - AMQP broker password. Special characters are handled automatically.
     *
     * @api
     */
    public const string QUEUE_AMQP_PASSWORD = 'SYMFONY_MESSENGER:QUEUE_AMQP_PASSWORD';

    /**
     * Specification:
     * - AMQP broker virtual host (e.g. "/eu-docker" or "eu-docker").
     *
     * @api
     */
    public const string QUEUE_AMQP_VIRTUAL_HOST = 'SYMFONY_MESSENGER:QUEUE_AMQP_VIRTUAL_HOST';
}
