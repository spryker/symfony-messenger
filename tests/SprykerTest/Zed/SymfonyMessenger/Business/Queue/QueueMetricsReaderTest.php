<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SymfonyMessenger\Business\Queue;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\QueueMetricsRequestTransfer;
use Spryker\Client\SymfonyMessenger\Messages\QueueMessage;
use Spryker\Client\SymfonyMessenger\SymfonyMessengerConfig;
use Spryker\Client\SymfonyMessenger\Transport\Amqp\AmqpTransport;
use Spryker\Client\SymfonyMessenger\Transport\Amqp\AmqpTransportFactory;
use Spryker\Zed\SymfonyMessenger\Business\Queue\QueueMetricsReader;
use SprykerTest\Zed\SymfonyMessenger\SymfonyMessengerZedTester;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group SymfonyMessenger
 * @group Business
 * @group Queue
 * @group QueueMetricsReaderTest
 * Add your own group annotations below this line
 */
class QueueMetricsReaderTest extends Unit
{
    protected const string TEST_QUEUE_NAME = 'test-queue-metrics-reader';

    protected SymfonyMessengerZedTester $tester;

    protected AmqpTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = $this->createAmqpTransport();
        $this->transport->createQueue(static::TEST_QUEUE_NAME);
        $this->transport->purgeQueue(static::TEST_QUEUE_NAME);
    }

    protected function tearDown(): void
    {
        $this->transport->purgeQueue(static::TEST_QUEUE_NAME);
        $this->transport->deleteQueue(static::TEST_QUEUE_NAME);

        parent::tearDown();
    }

    /**
     * @dataProvider provideMessageCounts
     */
    public function testReadReturnsCorrectMessageCount(int $messageCount): void
    {
        // Arrange
        for ($i = 0; $i < $messageCount; $i++) {
            $this->transport->send($this->buildMessage());
        }

        $requestTransfer = (new QueueMetricsRequestTransfer())->setQueueName(static::TEST_QUEUE_NAME);
        $reader = new QueueMetricsReader($this->tester->getLocator()->symfonyMessenger()->client());

        // Act
        $result = $reader->read($requestTransfer);

        // Assert
        $this->assertSame($messageCount, $result->getMessageCount());
    }

    /**
     * @return array<string, array<int>>
     */
    public function provideMessageCounts(): array
    {
        return [
            //'empty queue returns zero message count' => [0],
            'queue with 3 messages returns correct count' => [3],
        ];
    }

    protected function createAmqpTransport(): AmqpTransport
    {
        /** @var \Spryker\Client\SymfonyMessenger\Transport\Amqp\AmqpTransport $transport */
        $transport = (new AmqpTransportFactory())->createTransport(
            (new SymfonyMessengerConfig())->getAmqpConnectionDSN(),
            [
                'exchange' => [
                    'name' => static::TEST_QUEUE_NAME,
                ],
                'queues' => [
                    static::TEST_QUEUE_NAME => [
                        'binding_keys' => [
                            static::TEST_QUEUE_NAME,
                        ],
                    ],
                ],
            ],
            new Serializer(),
        );

        return $transport;
    }

    protected function buildMessage(): Envelope
    {
        return new Envelope((new QueueMessage())->setBody('{}'), [
            new BusNameStamp(static::TEST_QUEUE_NAME),
            new AmqpStamp(static::TEST_QUEUE_NAME),
        ]);
    }
}
