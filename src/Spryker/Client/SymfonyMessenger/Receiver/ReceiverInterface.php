<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Receiver;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;

interface ReceiverInterface
{
    public function acknowledgeFromQueueReceiveMessageTransfer(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void;

    public function rejectFromQueueReceiveMessageTransfer(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void;

    /**
     * @param array<string, mixed> $options
     */
    public function receiveMessage(string $queueName, array $options = []): QueueReceiveMessageTransfer;

    /**
     * @param array<string, mixed> $options
     *
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    public function receiveMessages(string $queueName, int $chunkSize = 100, array $options = []): array;
}
