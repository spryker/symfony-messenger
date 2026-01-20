<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Sender;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;

interface QueueSenderInterface
{
    public function sendMessage(string $queueName, QueueSendMessageTransfer $queueSendMessageTransfer): void;

    /**
     * @param array<\Generated\Shared\Transfer\QueueSendMessageTransfer> $queueSendMessageTransfers
     */
    public function sendMessages(string $queueName, array $queueSendMessageTransfers): void;

    public function handleError(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void;
}
