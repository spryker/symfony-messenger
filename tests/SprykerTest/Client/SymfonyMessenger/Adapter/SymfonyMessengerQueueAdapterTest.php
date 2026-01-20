<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SymfonyMessenger\Adapter;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use Spryker\Client\SymfonyMessenger\Adapter\SymfonyMessengerQueueAdapter;
use Spryker\Client\SymfonyMessenger\Control\QueueControlInterface;
use Spryker\Client\SymfonyMessenger\Receiver\ReceiverInterface;
use Spryker\Client\SymfonyMessenger\Sender\QueueSenderInterface;
use SprykerTest\Client\SymfonyMessenger\SymfonyMessengerClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SymfonyMessenger
 * @group Adapter
 * @group SymfonyMessengerQueueAdapterTest
 * Add your own group annotations below this line
 */
class SymfonyMessengerQueueAdapterTest extends Unit
{
    protected const string TEST_QUEUE_NAME = 'testQueue';

    protected SymfonyMessengerClientTester $tester;

    protected QueueSenderInterface $queueSenderMock;

    protected ReceiverInterface $receiverMock;

    protected QueueControlInterface $queueControlMock;

    protected SymfonyMessengerQueueAdapter $adapter;

    protected function _before(): void
    {
        parent::_before();

        $this->queueSenderMock = $this->createMock(QueueSenderInterface::class);
        $this->receiverMock = $this->createMock(ReceiverInterface::class);
        $this->queueControlMock = $this->createMock(QueueControlInterface::class);

        $this->adapter = new SymfonyMessengerQueueAdapter(
            $this->queueSenderMock,
            $this->receiverMock,
            $this->queueControlMock,
        );
    }

    public function testPurgeQueueReturnsTrue(): void
    {
        // Arrange
        $this->queueControlMock
            ->expects($this->once())
            ->method('purgeQueue')
            ->with(static::TEST_QUEUE_NAME)
            ->willReturn(true);

        // Act
        $result = $this->adapter->purgeQueue(static::TEST_QUEUE_NAME);

        // Assert
        $this->assertTrue($result);
    }

    public function testDeleteQueueCReturnsTrue(): void
    {
        // Arrange
        $this->queueControlMock
            ->expects($this->once())
            ->method('deleteQueue')
            ->with(static::TEST_QUEUE_NAME)
            ->willReturn(true);

        // Act
        $result = $this->adapter->deleteQueue(static::TEST_QUEUE_NAME);

        // Assert
        $this->assertTrue($result);
    }

    public function testSendMessageCallsSender(): void
    {
        // Arrange
        $queueSendMessageTransfer = new QueueSendMessageTransfer();
        $queueSendMessageTransfer->setBody('test message');

        $this->queueSenderMock
            ->expects($this->once())
            ->method('sendMessage')
            ->with(static::TEST_QUEUE_NAME, $queueSendMessageTransfer);

        // Act
        $this->adapter->sendMessage(static::TEST_QUEUE_NAME, $queueSendMessageTransfer);
    }

    public function testReceiveMessageCallsReceiver(): void
    {
        // Arrange
        $expectedTransfer = new QueueReceiveMessageTransfer();
        $expectedTransfer->setQueueName(static::TEST_QUEUE_NAME);

        $this->receiverMock
            ->expects($this->once())
            ->method('receiveMessage')
            ->with(static::TEST_QUEUE_NAME, [])
            ->willReturn($expectedTransfer);

        // Act
        $result = $this->adapter->receiveMessage(static::TEST_QUEUE_NAME);

        // Assert
        $this->assertSame($expectedTransfer, $result);
    }

    public function testReceiveMessagesCallsReceiver(): void
    {
        // Arrange
        $expectedTransfers = [
            (new QueueReceiveMessageTransfer())->setQueueName(static::TEST_QUEUE_NAME),
            (new QueueReceiveMessageTransfer())->setQueueName(static::TEST_QUEUE_NAME),
        ];

        $this->receiverMock
            ->expects($this->once())
            ->method('receiveMessages')
            ->with(static::TEST_QUEUE_NAME, 100, [])
            ->willReturn($expectedTransfers);

        // Act
        $result = $this->adapter->receiveMessages(static::TEST_QUEUE_NAME);

        // Assert
        $this->assertSame($expectedTransfers, $result);
    }

    public function testAcknowledgeCallsReceiver(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = new QueueReceiveMessageTransfer();

        $this->receiverMock
            ->expects($this->once())
            ->method('acknowledgeFromQueueReceiveMessageTransfer')
            ->with($queueReceiveMessageTransfer);

        // Act
        $this->adapter->acknowledge($queueReceiveMessageTransfer);
    }

    public function testRejectCallsReceiver(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = new QueueReceiveMessageTransfer();

        $this->receiverMock
            ->expects($this->once())
            ->method('rejectFromQueueReceiveMessageTransfer')
            ->with($queueReceiveMessageTransfer);

        // Act
        $this->adapter->reject($queueReceiveMessageTransfer);
    }

    public function testHandleErrorCallsSender(): void
    {
        // Arrange
        $queueReceiveMessageTransfer = new QueueReceiveMessageTransfer();

        $this->queueSenderMock
            ->expects($this->once())
            ->method('handleError')
            ->with($queueReceiveMessageTransfer);

        // Act
        $result = $this->adapter->handleError($queueReceiveMessageTransfer);

        // Assert
        $this->assertTrue($result);
    }
}
