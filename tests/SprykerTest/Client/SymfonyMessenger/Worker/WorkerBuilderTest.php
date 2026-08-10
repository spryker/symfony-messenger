<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SymfonyMessenger\Worker;

use Codeception\Test\Unit;
use Spryker\Client\SymfonyMessenger\MessageBus\MessageBusBuilderInterface;
use Spryker\Client\SymfonyMessenger\Worker\WorkerBuilder;
use SprykerTest\Client\SymfonyMessenger\SymfonyMessengerClientTester;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SymfonyMessenger
 * @group Worker
 * @group WorkerBuilderTest
 * Add your own group annotations below this line
 */
class WorkerBuilderTest extends Unit
{
    protected SymfonyMessengerClientTester $tester;

    public function testBuildOrdersTransportsByDescendingPriority(): void
    {
        // Arrange
        $workerBuilder = $this->createWorkerBuilder(
            ['low', 'high', 'medium'],
            ['low' => 10, 'high' => 30, 'medium' => 20],
        );

        // Act
        $worker = $workerBuilder->build(['low', 'high', 'medium']);

        // Assert
        $this->assertSame(['high', 'medium', 'low'], $worker->getMetadata()->getTransportNames());
    }

    public function testBuildTreatsMissingPriorityAsZero(): void
    {
        // Arrange
        $workerBuilder = $this->createWorkerBuilder(
            ['first', 'prioritized', 'second'],
            ['prioritized' => 5],
        );

        // Act
        $worker = $workerBuilder->build(['first', 'prioritized', 'second']);

        // Assert
        // "prioritized" wins; the two priority-less transports keep their original order.
        $this->assertSame(['prioritized', 'first', 'second'], $worker->getMetadata()->getTransportNames());
    }

    public function testBuildKeepsOriginalOrderForEqualPriorities(): void
    {
        // Arrange
        $workerBuilder = $this->createWorkerBuilder(
            ['a', 'b', 'c'],
            ['a' => 5, 'b' => 5, 'c' => 5],
        );

        // Act
        $worker = $workerBuilder->build(['a', 'b', 'c']);

        // Assert
        $this->assertSame(['a', 'b', 'c'], $worker->getMetadata()->getTransportNames());
    }

    /**
     * @param array<string> $transportNames
     * @param array<string, int> $transportPriorities
     */
    protected function createWorkerBuilder(array $transportNames, array $transportPriorities): WorkerBuilder
    {
        $transportMock = $this->createMock(TransportInterface::class);

        $availableTransports = [];
        foreach ($transportNames as $transportName) {
            $availableTransports[$transportName] = fn (array $options = []): TransportInterface => $transportMock;
        }

        $messageBusBuilderMock = $this->createMock(MessageBusBuilderInterface::class);
        $messageBusBuilderMock->method('getMessageBus')->willReturn($this->createMock(MessageBusInterface::class));

        return new WorkerBuilder($messageBusBuilderMock, $availableTransports, $transportPriorities);
    }
}
