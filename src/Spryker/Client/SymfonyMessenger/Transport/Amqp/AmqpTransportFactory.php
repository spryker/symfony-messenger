<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Transport\Amqp;

use SensitiveParameter;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransportFactory as SymfonyAmqpTransportFactory;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AmqpTransportFactory extends SymfonyAmqpTransportFactory
{
    /**
     * @param array<string, mixed> $options
     */
    public function createTransport(#[SensitiveParameter] string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        unset($options['transport_name']);

        return new AmqpTransport(
            Connection::fromDsn($dsn, $options),
            $serializer,
        );
    }
}
