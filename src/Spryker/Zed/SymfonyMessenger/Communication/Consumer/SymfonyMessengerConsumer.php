<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication\Consumer;

use Spryker\Client\SymfonyMessenger\SymfonyMessengerClientInterface;

class SymfonyMessengerConsumer implements SymfonyMessengerConsumerInterface
{
    public function __construct(protected SymfonyMessengerClientInterface $client)
    {
    }

    /**
     * @param array<string> $receivers
     * @param array<string, mixed> $options
     */
    public function consume(array $receivers, array $options = []): int
    {
        $this->client->consume($receivers, $options);

        return static::CODE_SUCCESS;
    }

    /**
     * @var int
     */
    protected const CODE_SUCCESS = 0;
}
