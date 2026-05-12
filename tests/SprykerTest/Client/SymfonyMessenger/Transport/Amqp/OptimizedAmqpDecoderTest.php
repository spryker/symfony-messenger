<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SymfonyMessenger\Transport\Amqp;

use Codeception\Test\Unit;
use Spryker\Client\SymfonyMessenger\Messages\QueueMessage;
use Spryker\Client\SymfonyMessenger\Transport\Amqp\OptimizedAmqpDecoder;
use SprykerTest\Client\SymfonyMessenger\SymfonyMessengerClientTester;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Serializer\SerializerInterface as SymfonySerializerInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SymfonyMessenger
 * @group Transport
 * @group Amqp
 * @group OptimizedAmqpDecoderTest
 * Add your own group annotations below this line
 */
class OptimizedAmqpDecoderTest extends Unit
{
    protected SymfonyMessengerClientTester $tester;

    public function testDecodeWithPlainBodyReturnsEnvelopeWithQueueMessage(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();

        // Act
        $envelope = $decoder->decode(['body' => 'plain body content', 'headers' => []]);

        // Assert
        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertInstanceOf(QueueMessage::class, $envelope->getMessage());
        $this->assertSame('plain body content', $envelope->getMessage()->getBody());
    }

    public function testDecodeWithWrappedBodyUnwrapsInnerBody(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $wrappedBody = json_encode(['body' => 'actual inner content']);

        // Act
        $envelope = $decoder->decode(['body' => $wrappedBody, 'headers' => []]);

        // Assert
        $this->assertSame('actual inner content', $envelope->getMessage()->getBody());
    }

    public function testDecodeWithMissingBodyDefaultsToEmptyString(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();

        // Act
        $envelope = $decoder->decode(['headers' => []]);

        // Assert
        $this->assertSame('', $envelope->getMessage()->getBody());
    }

    public function testDecodeExtractsSerializerStampContextFromHeaders(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $stampContext = ['groups' => ['Default']];
        $headerKey = sprintf('X-Message-Stamp-%s', SerializerStamp::class);

        $encodedEnvelope = [
            'body' => 'body',
            'headers' => [
                $headerKey => json_encode([['context' => $stampContext]]),
            ],
        ];

        // Act
        $envelope = $decoder->decode($encodedEnvelope);

        // Assert
        /** @var \Symfony\Component\Messenger\Stamp\SerializerStamp|null $stamp */
        $stamp = $envelope->last(SerializerStamp::class);

        $this->assertInstanceOf(SerializerStamp::class, $stamp);
        $this->assertSame($stampContext, $stamp->getContext());
    }

    public function testDecodeWithMissingStampHeaderAttachesEmptySerializerStamp(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();

        // Act
        $envelope = $decoder->decode(['body' => 'body', 'headers' => []]);

        // Assert
        /** @var \Symfony\Component\Messenger\Stamp\SerializerStamp|null $stamp */
        $stamp = $envelope->last(SerializerStamp::class);

        $this->assertInstanceOf(SerializerStamp::class, $stamp);
        $this->assertSame([], $stamp->getContext());
    }

    public function testDecodeWithMalformedStampHeaderFallsBackToEmptySerializerStamp(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $headerKey = sprintf('X-Message-Stamp-%s', SerializerStamp::class);

        $encodedEnvelope = [
            'body' => 'body',
            'headers' => [
                $headerKey => 'not valid json {{{',
            ],
        ];

        // Act
        $envelope = $decoder->decode($encodedEnvelope);

        // Assert
        /** @var \Symfony\Component\Messenger\Stamp\SerializerStamp|null $stamp */
        $stamp = $envelope->last(SerializerStamp::class);

        $this->assertInstanceOf(SerializerStamp::class, $stamp);
        $this->assertSame([], $stamp->getContext());
    }

    public function testDecodeWithStampMissingContextKeyFallsBackToEmptySerializerStamp(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $headerKey = sprintf('X-Message-Stamp-%s', SerializerStamp::class);

        $encodedEnvelope = [
            'body' => 'body',
            'headers' => [
                $headerKey => json_encode([['no_context_key' => []]]),
            ],
        ];

        // Act
        $envelope = $decoder->decode($encodedEnvelope);

        // Assert
        /** @var \Symfony\Component\Messenger\Stamp\SerializerStamp|null $stamp */
        $stamp = $envelope->last(SerializerStamp::class);

        $this->assertInstanceOf(SerializerStamp::class, $stamp);
        $this->assertSame([], $stamp->getContext());
    }

    public function testDecodeExtractsBusNameStampFromHeaders(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $headerKey = sprintf('X-Message-Stamp-%s', BusNameStamp::class);

        $encodedEnvelope = [
            'body' => 'body',
            'headers' => [
                $headerKey => json_encode([['busName' => 'messenger.bus.default']]),
            ],
        ];

        // Act
        $envelope = $decoder->decode($encodedEnvelope);

        // Assert
        /** @var \Symfony\Component\Messenger\Stamp\BusNameStamp|null $stamp */
        $stamp = $envelope->last(BusNameStamp::class);

        $this->assertInstanceOf(BusNameStamp::class, $stamp);
        $this->assertSame('messenger.bus.default', $stamp->getBusName());
    }

    public function testDecodeWithMissingBusNameStampHeaderSkipsStamp(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();

        // Act
        $envelope = $decoder->decode(['body' => 'body', 'headers' => []]);

        // Assert
        $this->assertNull($envelope->last(BusNameStamp::class));
    }

    public function testDecodeWithMalformedBusNameStampHeaderSkipsStamp(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $headerKey = sprintf('X-Message-Stamp-%s', BusNameStamp::class);

        $encodedEnvelope = [
            'body' => 'body',
            'headers' => [
                $headerKey => 'not valid json {{{',
            ],
        ];

        // Act
        $envelope = $decoder->decode($encodedEnvelope);

        // Assert
        $this->assertNull($envelope->last(BusNameStamp::class));
    }

    public function testDecodeWithBusNameStampMissingBusNameKeySkipsStamp(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $headerKey = sprintf('X-Message-Stamp-%s', BusNameStamp::class);

        $encodedEnvelope = [
            'body' => 'body',
            'headers' => [
                $headerKey => json_encode([['unknownKey' => 'value']]),
            ],
        ];

        // Act
        $envelope = $decoder->decode($encodedEnvelope);

        // Assert
        $this->assertNull($envelope->last(BusNameStamp::class));
    }

    public function testDecodeExtractsAmqpStampFromHeaders(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $headerKey = sprintf('X-Message-Stamp-%s', AmqpStamp::class);

        $encodedEnvelope = [
            'body' => 'body',
            'headers' => [
                $headerKey => json_encode([['routingKey' => 'retry', 'flags' => 0, 'attributes' => ['key' => 'val']]]),
            ],
        ];

        // Act
        $envelope = $decoder->decode($encodedEnvelope);

        // Assert
        /** @var \Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp|null $stamp */
        $stamp = $envelope->last(AmqpStamp::class);

        $this->assertInstanceOf(AmqpStamp::class, $stamp);
        $this->assertSame('retry', $stamp->getRoutingKey());
        $this->assertSame(0, $stamp->getFlags());
        $this->assertSame(['key' => 'val'], $stamp->getAttributes());
    }

    public function testDecodeWithMissingAmqpStampHeaderSkipsStamp(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();

        // Act
        $envelope = $decoder->decode(['body' => 'body', 'headers' => []]);

        // Assert
        $this->assertNull($envelope->last(AmqpStamp::class));
    }

    public function testDecodeWithMalformedAmqpStampHeaderSkipsStamp(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $headerKey = sprintf('X-Message-Stamp-%s', AmqpStamp::class);

        $encodedEnvelope = [
            'body' => 'body',
            'headers' => [
                $headerKey => 'not valid json {{{',
            ],
        ];

        // Act
        $envelope = $decoder->decode($encodedEnvelope);

        // Assert
        $this->assertNull($envelope->last(AmqpStamp::class));
    }

    public function testDecodeWithPartialAmqpStampDataUsesDefaults(): void
    {
        // Arrange
        $decoder = new OptimizedAmqpDecoder();
        $headerKey = sprintf('X-Message-Stamp-%s', AmqpStamp::class);

        $encodedEnvelope = [
            'body' => 'body',
            'headers' => [
                $headerKey => json_encode([[]]),
            ],
        ];

        // Act
        $envelope = $decoder->decode($encodedEnvelope);

        // Assert
        /** @var \Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp|null $stamp */
        $stamp = $envelope->last(AmqpStamp::class);

        $this->assertInstanceOf(AmqpStamp::class, $stamp);
        $this->assertSame('', $stamp->getRoutingKey());
        $this->assertSame(AMQP_NOPARAM, $stamp->getFlags());
        $this->assertSame([], $stamp->getAttributes());
    }

    public function testEncodeDelegatestoParentSerializerAndReturnsExpectedStructure(): void
    {
        // Arrange
        $symfonySerializerMock = $this->createMock(SymfonySerializerInterface::class);
        $symfonySerializerMock
            ->method('serialize')
            ->willReturn('{"body":"test"}');

        $decoder = new OptimizedAmqpDecoder($symfonySerializerMock);
        $envelope = new Envelope((new QueueMessage())->setBody('test'));

        // Act
        $result = $decoder->encode($envelope);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('body', $result);
        $this->assertArrayHasKey('headers', $result);
        $this->assertSame('{"body":"test"}', $result['body']);
        $this->assertSame(QueueMessage::class, $result['headers']['type']);
    }
}
