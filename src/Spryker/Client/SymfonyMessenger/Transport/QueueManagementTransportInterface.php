<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Transport;

use Symfony\Component\Messenger\Envelope;

interface QueueManagementTransportInterface
{
    /**
     * @param array<string, mixed> $options
     *
     * @return array<int, mixed>
     */
    public function createQueue(string $queueName, array $options = []): array;

    /**
     * @param array<string, mixed> $options
     */
    public function purgeQueue(string $queueName, array $options = []): bool;

    /**
     * @param array<string, mixed> $options
     */
    public function deleteQueue(string $queueName, array $options = []): bool;

    /**
     * @param \Symfony\Component\Messenger\Envelope $envelope
     * @param int $flags
     *
     * @return void
     */
    public function rejectWithFlag(Envelope $envelope, int $flags = AMQP_NOPARAM): void;

    /**
     * @return array<\Symfony\Component\Messenger\Envelope>
     */
    public function consumeMessages(string $queueName, int $chunkSize = 100): array;

    /**
     * @param array<string> $queueNames
     */
    public function areQueuesEmpty(array $queueNames): bool;
}
