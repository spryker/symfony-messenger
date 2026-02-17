<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SymfonyMessenger\Worker;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use SplObjectStorage;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerRateLimitedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Messenger\Exception\EnvelopeAwareExceptionInterface;
use Symfony\Component\Messenger\Exception\RejectRedeliveredMessageException;
use Symfony\Component\Messenger\Exception\RuntimeException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\AckStamp;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;
use Symfony\Component\Messenger\Stamp\FlushBatchHandlersStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\NoAutoAckStamp;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Transport\Receiver\QueueReceiverInterface;
use Symfony\Component\Messenger\Worker as SymfonyWorker;
use Symfony\Component\Messenger\WorkerMetadata;
use Throwable;

/**
 * Symfony Worker class was extended to add more info on execute and do not hide errors on dispatch. Almost all file content was copied and the original class is used mostly to keep the same type.
 */
class Worker extends SymfonyWorker implements ErrorAwareWorkerInterface
{
    protected bool $shouldStop = false;

    protected WorkerMetadata $metadata;

    /**
     * @var array<array{0: string, 1: \Symfony\Component\Messenger\Envelope, 2: ?\Throwable}>
     */
    protected array $acks = [];

    protected SplObjectStorage $unacks;

    protected bool $hadErrors = false;

    /**
     * @param array<\Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface> $receivers Where the key is the transport name
     * @param array<string, \Symfony\Component\RateLimiter\LimiterFactory>|null $rateLimiters Where the key is the transport name
     */
    public function __construct(
        protected array $receivers,
        protected MessageBusInterface $bus,
        protected ?EventDispatcherInterface $eventDispatcher = null,
        protected ?LoggerInterface $logger = null,
        protected ?array $rateLimiters = null,
        protected ClockInterface $clock = new Clock(),
    ) {
        $this->metadata = new WorkerMetadata([
            'transportNames' => array_keys($receivers),
        ]);
        $this->unacks = new SplObjectStorage();
    }

    /**
     * Receive the messages and dispatch them to the bus.
     *
     * Valid options are:
     *  * sleep (default: 1000000): Time in microseconds to sleep after no messages are found
     *  * queues: The queue names to consume from, instead of consuming from all queues. When this is used, all receivers must implement the QueueReceiverInterface
     *
     * @param array<string, mixed> $options
     *
     * @throws \Symfony\Component\Messenger\Exception\RuntimeException
     */
    public function run(array $options = []): void
    {
        $options = array_merge([
            'sleep' => 1000000,
        ], $options);
        $queueNames = $options['queues'] ?? null;

        /** @var \Symfony\Component\Console\Output\OutputInterface|null $output */
        $output = $options['output'] ?? null;

        $start = $this->clock->now();

        //Added an output
        $output?->writeln(sprintf('Starting worker at %s', $start->format('Y-m-d H:i:s')));

        $this->metadata->set(['queueNames' => $queueNames]);

        $this->eventDispatcher?->dispatch(new WorkerStartedEvent($this));

        if ($queueNames) {
            // if queue names are specified, all receivers must implement the QueueReceiverInterface
            foreach ($this->receivers as $transportName => $receiver) {
                if (!$receiver instanceof QueueReceiverInterface) {
                    throw new RuntimeException(sprintf('Receiver for "%s" does not implement "%s".', $transportName, QueueReceiverInterface::class));
                }
            }
        }

        while (!$this->shouldStop) {
            $envelopeHandled = false;
            $envelopeHandledStart = $this->clock->now();
            foreach ($this->receivers as $transportName => $receiver) {
                if ($queueNames) {
                    $envelopes = $receiver->getFromQueues($queueNames);
                } else {
                    $envelopes = $receiver->get();
                }

                foreach ($envelopes as $envelope) {
                    $envelopeHandled = true;

                    //Added an output
                    $output?->writeln(sprintf('Received message from transport "%s".', $transportName));

                    $this->rateLimit($transportName);
                    $this->handleMessage($envelope, $transportName, $output);

                    //Added an output
                    $output?->writeln(sprintf('Processed message %s from transport "%s".', $envelope->getMessage()::class, $transportName));
                    $this->eventDispatcher?->dispatch(new WorkerRunningEvent($this, false));

                    if ($this->shouldStop) {
                        break 2;
                    }
                }

                if ($envelopeHandled) {
                    break;
                }
            }

            if (!$envelopeHandled && $this->flush(false)) {
                continue;
            }

            if (!$envelopeHandled) {
                $this->eventDispatcher?->dispatch(new WorkerRunningEvent($this, true));

                $sleep = (int)($options['sleep'] - 1e6 * ($this->clock->now()->format('U.u') - $envelopeHandledStart->format('U.u')));
                if ($sleep > 0) {
                    $this->clock->sleep($sleep / 1e6);
                }
            }

            //Added a non event driven time limit check to be able to stop the worker after a certain time.
            if (isset($options['time-limit']) && $this->clock->now()->diff($start)->s >= $options['time-limit']) {
                $output->writeln('Worker time limit of ' . $options['time-limit'] . ' seconds reached, stopping worker.');
                $this->logger?->info('Worker time limit of {time_limit} seconds reached, stopping worker.', ['time_limit' => $options['time-limit']]);

                break;
            }
        }

        $this->flush(true);
        $this->eventDispatcher?->dispatch(new WorkerStoppedEvent($this));
    }

    public function hadErrors(): bool
    {
        return $this->hadErrors;
    }

    protected function handleMessage(Envelope $envelope, string $transportName, ?OutputInterface $output = null): void
    {
        $event = new WorkerMessageReceivedEvent($envelope, $transportName);
        $this->eventDispatcher?->dispatch($event);
        $envelope = $event->getEnvelope();

        if (!$event->shouldHandle()) {
            return;
        }

        $acked = false;
        $ack = function (Envelope $envelope, ?Throwable $e = null) use ($transportName, &$acked) {
            $acked = true;
            $this->acks[] = [$transportName, $envelope, $e];
        };

        //This part was unpacked to be able to catch exceptions thrown by the bus and not hide them in the logger.
        $e = null;
        $envelope = $this->bus->dispatch($envelope->with(new ReceivedStamp($transportName), new ConsumedByWorkerStamp(), new AckStamp($ack)));
        //Added command output if there were an error in the handler
        $handledStamp = $envelope->last(HandledStamp::class);
        if ($handledStamp && $handledStamp->getResult()) {
            $output?->writeln(sprintf('Output: %s', $handledStamp->getResult()));
            $this->hadErrors = true;
        }

        $noAutoAckStamp = $envelope->last(NoAutoAckStamp::class);

        if (!$acked && !$noAutoAckStamp) {
            $this->acks[] = [$transportName, $envelope, $e];
        } elseif ($noAutoAckStamp) {
            $this->unacks[$noAutoAckStamp->getHandlerDescriptor()->getBatchHandler()] = [$envelope->withoutAll(AckStamp::class), $transportName, &$acked];
        }

        $this->ack();
    }

    protected function ack(): bool
    {
        $acks = $this->acks;
        $this->acks = [];

        foreach ($acks as [$transportName, $envelope, $e]) {
            $receiver = $this->receivers[$transportName];

            if ($e !== null) {
                $rejectFirst = $e instanceof RejectRedeliveredMessageException;
                if ($rejectFirst) {
                    // redelivered messages are rejected first so that continuous failures in an event listener or while
                    // publishing for retry does not cause infinite redelivery loops
                    $receiver->reject($envelope);
                }

                if ($e instanceof EnvelopeAwareExceptionInterface && $e->getEnvelope() !== null) {
                    $envelope = $e->getEnvelope();
                }

                $failedEvent = new WorkerMessageFailedEvent($envelope, $transportName, $e);

                $this->eventDispatcher?->dispatch($failedEvent);
                $envelope = $failedEvent->getEnvelope();

                if (!$rejectFirst) {
                    $receiver->reject($envelope);
                }

                continue;
            }

            $handledEvent = new WorkerMessageHandledEvent($envelope, $transportName);
            $this->eventDispatcher?->dispatch($handledEvent);
            $envelope = $handledEvent->getEnvelope();

            if ($this->logger !== null) {
                $message = $envelope->getMessage();
                $context = [
                    'class' => $message::class,
                ];
                $this->logger->info('{class} was handled successfully (acknowledging to transport).', $context);
            }

            $receiver->ack($envelope);
        }

        return (bool)$acks;
    }

    protected function rateLimit(string $transportName): void
    {
        if (!$this->rateLimiters) {
            return;
        }

        if (!array_key_exists($transportName, $this->rateLimiters)) {
            return;
        }

        /** @var \Symfony\Component\RateLimiter\LimiterInterface $rateLimiter */
        $rateLimiter = $this->rateLimiters[$transportName]->create();
        if ($rateLimiter->consume()->isAccepted()) {
            return;
        }

        $this->logger?->info('Transport {transport} is being rate limited, waiting for token to become available...', ['transport' => $transportName]);

        $this->eventDispatcher?->dispatch(new WorkerRateLimitedEvent($rateLimiter, $transportName));
        $rateLimiter->reserve()->wait();
        $rateLimiter->consume();
    }

    protected function flush(bool $force): bool
    {
        $unacks = $this->unacks;

        if (!$unacks->count()) {
            return false;
        }

        $this->unacks = new SplObjectStorage();

        foreach ($unacks as $batchHandler) {
            [$envelope, $transportName, $acked] = $unacks[$batchHandler];
            try {
                $e = null;
                $this->bus->dispatch($envelope->with(new FlushBatchHandlersStamp($force)));
                unset($unacks[$batchHandler], $batchHandler);
            } catch (Throwable $e) {
                $envelope = $envelope->withoutAll(NoAutoAckStamp::class);
                $this->acks[] = [$transportName, $envelope, $e];

                continue;
            }

            $noAutoAckStamp = $envelope->last(NoAutoAckStamp::class);

            if (!$acked && !$noAutoAckStamp) {
                $this->acks[] = [$transportName, $envelope, $e];
            } elseif ($noAutoAckStamp) {
                $this->unacks[$noAutoAckStamp->getHandlerDescriptor()->getBatchHandler()] = [$envelope->withoutAll(AckStamp::class), $transportName, &$acked];
            }
        }

        return $this->ack();
    }

    public function stop(): void
    {
        $this->logger?->info('Stopping worker.', ['transport_names' => $this->metadata->getTransportNames()]);

        $this->shouldStop = true;
    }

    public function getMetadata(): WorkerMetadata
    {
        return $this->metadata;
    }
}
