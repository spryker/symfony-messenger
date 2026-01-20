<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Stamp;

use RuntimeException;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Messenger\Stamp\StampInterface;
use const AMQP_NOPARAM;

/**
 * Provides a list of stamps that are added on the message sending to the queue.
 * BusNameStamp is mandatory to route the message to the desired queue.
 * Queue stamp (AmqpStamp) is added when additional routing is needed (like pushing messages into the error queue).
 */
class QueueStampStackBuilder implements StampStackBuilderInterface
{
    /**
     * @param array<\Closure> $additionalStamps Any other additional stamps that should be added to the message. Options will be passed to a closure as a parameter.
     */
    public function __construct(protected array $additionalStamps)
    {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<\Symfony\Component\Messenger\Stamp\StampInterface>
     */
    public function buildStack(array $options = []): array
    {
        return array_merge($this->getAdditionalQueueStamps($options), [
            $this->createBusNameStamp($options['queue_name'] ?? null),
            $this->createSerializerStamp($options['headers'] ?? []),
        ]);
    }

    protected function createBusNameStamp(?string $queueName): BusNameStamp
    {
        if ($queueName === null) {
            throw new RuntimeException('Please make sure that queue name was provided for the BusNameStamp as it used to rout your message to desired queue.');
        }

        return new BusNameStamp($queueName);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createSerializerStamp(array $data = []): SerializerStamp
    {
        return new SerializerStamp($data);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<\Symfony\Component\Messenger\Stamp\StampInterface>
     */
    protected function getAdditionalQueueStamps(array $options = []): array
    {
        $readyStamps = [];
        foreach ($this->additionalStamps as $additionalStampClosure) {
            $readyStamps[] = $additionalStampClosure($options);
        }

        if ($options['queue_options'] ?? false) {
            $readyStamps[] = $this->buildQueueStamp($options['queue_options']);
        }

        return $readyStamps;
    }

    /**
     * @param array<string, mixed> $queueOptions
     *
     * @return \Symfony\Component\Messenger\Stamp\StampInterface
     */
    protected function buildQueueStamp(array $queueOptions = []): StampInterface
    {
        return new AmqpStamp($queueOptions['routing_key'] ?? '', AMQP_NOPARAM, $queueOptions['attributes'] ?? []);
    }
}
