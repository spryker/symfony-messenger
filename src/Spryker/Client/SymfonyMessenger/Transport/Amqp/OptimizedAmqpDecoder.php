<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Transport\Amqp;

use Spryker\Client\SymfonyMessenger\Messages\QueueMessage;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Messenger\Transport\Serialization\Serializer;

class OptimizedAmqpDecoder extends Serializer
{
    /**
     * Builds a Symfony Envelope directly from raw AMQP data, skipping full Symfony Serializer
     * deserialization. The body is preserved as-is in QueueMessage — downstream code only reads the raw body.
     * Encoding is delegated to the parent Symfony Serializer.
     *
     * @param array<string, mixed> $encodedEnvelope
     */
    public function decode(array $encodedEnvelope): Envelope
    {
        $message = (new QueueMessage())->setBody($this->unwrapBody($encodedEnvelope['body'] ?? ''));
        $headers = $encodedEnvelope['headers'] ?? [];

        $stamps = [$this->extractSerializerStamp($headers)];

        $busNameStamp = $this->extractBusNameStamp($headers);
        if ($busNameStamp !== null) {
            $stamps[] = $busNameStamp;
        }

        $amqpStamp = $this->extractAmqpStamp($headers);
        if ($amqpStamp !== null) {
            $stamps[] = $amqpStamp;
        }

        return new Envelope($message, $stamps);
    }

    protected function unwrapBody(string $body): string
    {
        $decoded = json_decode($body, true);

        if (is_array($decoded) && isset($decoded['body']) && is_string($decoded['body'])) {
            return $decoded['body'];
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function extractBusNameStamp(array $headers): ?BusNameStamp
    {
        $headerKey = sprintf('X-Message-Stamp-%s', BusNameStamp::class);

        if (!isset($headers[$headerKey])) {
            return null;
        }

        $stampData = json_decode((string)$headers[$headerKey], true);

        if (!is_array($stampData) || !isset($stampData[0]['busName'])) {
            return null;
        }

        return new BusNameStamp($stampData[0]['busName']);
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function extractAmqpStamp(array $headers): ?AmqpStamp
    {
        $headerKey = sprintf('X-Message-Stamp-%s', AmqpStamp::class);

        if (!isset($headers[$headerKey])) {
            return null;
        }

        $stampData = json_decode((string)$headers[$headerKey], true);

        if (!is_array($stampData) || !isset($stampData[0])) {
            return null;
        }

        $data = $stampData[0];

        return new AmqpStamp(
            $data['routingKey'] ?? '',
            (int)($data['flags'] ?? AMQP_NOPARAM),
            $data['attributes'] ?? [],
        );
    }

    /**
     * @param array<string, mixed> $headers
     */
    protected function extractSerializerStamp(array $headers): SerializerStamp
    {
        $headerKey = sprintf('X-Message-Stamp-%s', SerializerStamp::class);

        if (!isset($headers[$headerKey])) {
            return new SerializerStamp([]);
        }

        $stampData = json_decode((string)$headers[$headerKey], true);

        if (!is_array($stampData) || !isset($stampData[0]['context'])) {
            return new SerializerStamp([]);
        }

        return new SerializerStamp($stampData[0]['context']);
    }
}
