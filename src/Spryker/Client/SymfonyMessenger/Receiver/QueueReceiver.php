<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Receiver;

use Generated\Shared\Transfer\QueueReceiveMessageTransfer;
use Generated\Shared\Transfer\QueueSendMessageTransfer;
use RuntimeException;
use Spryker\Client\SymfonyMessenger\Transport\QueueManagementTransportInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class QueueReceiver implements ReceiverInterface
{
    /**
     * Envelopes are stored after receiving to be able to acknowledge or reject them later.
     * It's done this way because we work with transfers that cannot transport non-scalar or non-transfer values and the reject/ack methods need the original Envelope object.
     *
     * @var array<int|string, \Symfony\Component\Messenger\Envelope>
     */
    protected static array $envelopes = [];

    public function __construct(
        protected TransportInterface $queueTransport,
        protected SerializerInterface $serializer,
    ) {
    }

    public function acknowledgeFromQueueReceiveMessageTransfer(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void
    {
        if ($queueReceiveMessageTransfer->getQueueName() === null) {
            return;
        }

        $envelope = $this->buildEnvelope($queueReceiveMessageTransfer);
        $this->queueTransport->ack($envelope);

        unset(static::$envelopes[$queueReceiveMessageTransfer->getAmqpEnvelopId()]);
    }

    public function rejectFromQueueReceiveMessageTransfer(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): void
    {
        if ($queueReceiveMessageTransfer->getQueueName() === null) {
            return;
        }

        $envelope = $this->buildEnvelope($queueReceiveMessageTransfer);
        $queueReceiveMessageTransfer->getRequeue() && $this->queueTransport instanceof QueueManagementTransportInterface ?
            $this->queueTransport->rejectWithFlag($envelope, AMQP_REQUEUE) :
            $this->queueTransport->reject($envelope);

        unset(static::$envelopes[$queueReceiveMessageTransfer->getAmqpEnvelopId()]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function receiveMessage(string $queueName, array $options = []): QueueReceiveMessageTransfer
    {
        /** @var \Generator $iterator */
        $iterator = $this->getOne($queueName);
        if (!$iterator->valid()) {
            return new QueueReceiveMessageTransfer();
        }

        return $this->transformEnvelopeToQueueMessageTransfer($iterator->current(), $queueName, true);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    public function receiveMessages(string $queueName, int $chunkSize = 100, array $options = []): array
    {
        if ($this->queueTransport instanceof QueueManagementTransportInterface) {
            return $this->consumeMessages($queueName, $chunkSize);
        }
        $queueReceiveMessageTransfers = [];

        while ($chunkSize >= 0) {
            /** @var \Generator $envelopeGenerator */
            $envelopeGenerator = $this->getOne($queueName);
            if (!$envelopeGenerator->valid()) {
                return $queueReceiveMessageTransfers;
            }

            $queueReceiveMessageTransfers[] = $this->transformEnvelopeToQueueMessageTransfer($envelopeGenerator->current(), $queueName);

            $chunkSize--;
        }

        return $queueReceiveMessageTransfers;
    }

    /**
     * @return array<\Generated\Shared\Transfer\QueueReceiveMessageTransfer>
     */
    protected function consumeMessages(string $queueName, int $chunkSize = 100): array
    {
        /** @var \Spryker\Client\SymfonyMessenger\Transport\QueueManagementTransportInterface $transport */ // @phpstan-ignore varTag.nativeType
        $transport = $this->queueTransport;
        $envelopes = $transport->consumeMessages($queueName, $chunkSize);
        $queueReceiveMessageTransfers = [];
        foreach ($envelopes as $envelope) {
            $queueReceiveMessageTransfers[] = $this->transformEnvelopeToQueueMessageTransfer($envelope, $queueName);
        }

        return $queueReceiveMessageTransfers;
    }

    protected function transformEnvelopeToQueueMessageTransfer(
        Envelope $envelope,
        string $queueName,
        bool $reQueueOnReject = false,
    ): QueueReceiveMessageTransfer {
        $queueReceiveMessageTransfer = new QueueReceiveMessageTransfer();
        $amqpStamp = $envelope->last(AmqpReceivedStamp::class);
        $serializerStamp = $envelope->last(SerializerStamp::class);
        $amqpEnvelope = $amqpStamp->getAmqpEnvelope();

        $amqpEnvelopeId = spl_object_id($envelope);
        static::$envelopes[$amqpEnvelopeId] = $envelope;
        $queueReceiveMessageTransfer->setAmqpEnvelopId((string)$amqpEnvelopeId);

        /** @var \Spryker\Client\SymfonyMessenger\Messages\BodyAwareMessageInterface $message */
        $message = $envelope->getMessage();
        $queueSendMessageTransfer = new QueueSendMessageTransfer();
        $queueSendMessageTransfer->setBody($message->getBody());
        $queueSendMessageTransfer->setHeaders($serializerStamp->getContext());
        $queueReceiveMessageTransfer->setQueueMessage($queueSendMessageTransfer);
        $queueReceiveMessageTransfer->setQueueName($queueName);
        $queueReceiveMessageTransfer->setDeliveryTag((string)$amqpEnvelope->getDeliveryTag());
        // get from options
        $queueReceiveMessageTransfer->setRequeue($reQueueOnReject);
        $queueReceiveMessageTransfer->setRoutingKey($amqpEnvelope->getRoutingKey());

        return $queueReceiveMessageTransfer;
    }

    /**
     * @return iterable<\Symfony\Component\Messenger\Envelope>
     */
    protected function getOne(string $queue): iterable
    {
        if (!($this->queueTransport instanceof QueueReceiverInterface)) {
            return $this->queueTransport->get();
        }

        return $this->queueTransport->getFromQueues([$queue]);
    }

    /**
     * @throws \RuntimeException
     */
    protected function buildEnvelope(QueueReceiveMessageTransfer $queueReceiveMessageTransfer): Envelope
    {
        if (!isset(static::$envelopes[$queueReceiveMessageTransfer->getAmqpEnvelopId()])) {
            throw new RuntimeException(sprintf('AMQPEnvelope with id "%s" not found.', $queueReceiveMessageTransfer->getAmqpEnvelopId()));
        }

        return static::$envelopes[$queueReceiveMessageTransfer->getAmqpEnvelopId()];
    }
}
