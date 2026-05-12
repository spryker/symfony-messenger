<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Transport\Amqp;

use AMQPEnvelope;
use AMQPException;
use Generated\Shared\Transfer\QueueInformationCollectionTransfer;
use Generated\Shared\Transfer\QueueInformationTransfer;
use Spryker\Client\SymfonyMessenger\Transport\QueueManagementTransportInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceiver;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpSender;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpTransport as SymfonyAmqpTransport;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class AmqpTransport extends SymfonyAmqpTransport implements QueueManagementTransportInterface
{
    protected SerializerInterface $serializer;

    /**
     * @var array<\Symfony\Component\Messenger\Envelope>
     */
    protected array $consumedMessages = [];

    public function __construct(
        protected Connection $connection,
        ?SerializerInterface $serializer = null,
        protected ?AmqpReceiver $receiver = null,
        protected ?AmqpSender $sender = null
    ) {
        $this->serializer = $serializer ?? new Serializer();
        parent::__construct($connection, $this->serializer);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<int, mixed>
     */
    public function createQueue(string $queueName, array $options = []): array
    {
        $this->connection->queue($queueName)->declareQueue();

        // Return an empty array to comply with the method signature.
        return [];
    }

    /**
     * @param array<string> $queueNames
     */
    public function areQueuesEmpty(array $queueNames): bool
    {
        foreach ($queueNames as $queueName) {
            $queue = $this->connection->queue($queueName);
            $messageCount = $queue->declareQueue();

            if ($messageCount > 0) {
                return false;
            }
        }

        return true;
    }

    public function getQueues(array $queueNames): QueueInformationCollectionTransfer
    {
        $rabbitMqQueueCollectionTransfer = new QueueInformationCollectionTransfer();
        foreach ($queueNames as $queueName) {
            $queue = $this->connection->queue($queueName);
            $messageCount = $queue->declareQueue();

            $rabbitMqQueueTransfer = new QueueInformationTransfer();
            $rabbitMqQueueTransfer->setName($queueName);
            $rabbitMqQueueTransfer->setReadyCount($messageCount);
            $rabbitMqQueueCollectionTransfer->addQueue($rabbitMqQueueTransfer);
        }

        return $rabbitMqQueueCollectionTransfer;
    }

    /**
     * @return array<\Symfony\Component\Messenger\Envelope>
     */
    public function consumeMessages(string $queueName, int $chunkSize = 100): array
    {
        $queue = $this->connection->queue($queueName);
        $messages = $queue->declareQueue();
        if ($messages === 0) {
            return [];
        }
        $chunkSize = min($chunkSize, $messages);
        $queue->getChannel()->qos(0, $chunkSize);
        $queue->consume(function (AMQPEnvelope $amqpEnvelope) use ($chunkSize, $queueName) {
            static $counter = 0;
            $counter++;

            $envelope = $this->serializer->decode([
                'body' => ($amqpEnvelope->getBody() ?: ''),
                'headers' => $amqpEnvelope->getHeaders(),
            ]);

            $this->consumedMessages[] = $envelope->with(new AmqpReceivedStamp($amqpEnvelope, $queueName));

            if ($counter >= $chunkSize) {
                $counter = 0;

                return false;
            }

            return true;
        });

        $messages = $this->consumedMessages;
        $this->consumedMessages = [];

        $queue->cancel();

        return $messages;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function purgeQueue(string $queueName, array $options = []): bool
    {
        return (bool)$this->connection->queue($queueName)->purge();
    }

    /**
     * @param array<string, mixed> $options
     */
    public function deleteQueue(string $queueName, array $options = []): bool
    {
        return (bool)$this->connection->queue($queueName)->delete();
    }

    public function setup(): void
    {
        $this->connection->setup();
    }

    public function rejectWithFlag(Envelope $envelope, int $flags = AMQP_NOPARAM): void
    {
        $amqpReceivedStamp = $envelope->last(AmqpReceivedStamp::class);
        if ($amqpReceivedStamp === null) {
            throw new LogicException('No "AmqpReceivedStamp" stamp found on the Envelope.');
        }

        $this->rejectWithFlagsAmqpEnvelope(
            $amqpReceivedStamp->getAmqpEnvelope(),
            $amqpReceivedStamp->getQueueName(),
            $flags,
        );
    }

    protected function rejectWithFlagsAmqpEnvelope(AMQPEnvelope $amqpEnvelope, string $queueName, int $flags = AMQP_NOPARAM): void
    {
        try {
            $this->connection->nack($amqpEnvelope, $queueName, $flags);
        } catch (AMQPException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }
    }

    protected function getReceiver(): AmqpReceiver
    {
        return $this->receiver ??= new AmqpReceiver($this->connection, $this->serializer);
    }

    protected function getSender(): AmqpSender
    {
        return $this->sender ??= new AmqpSender($this->connection, $this->serializer);
    }
}
