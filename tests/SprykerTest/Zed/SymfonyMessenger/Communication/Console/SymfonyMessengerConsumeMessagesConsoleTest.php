<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SymfonyMessenger\Communication\Console;

use Codeception\Test\Unit;
use ReflectionMethod;
use Spryker\Zed\SymfonyMessenger\Communication\Console\SymfonyMessengerConsumeMessagesConsole;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group SymfonyMessenger
 * @group Communication
 * @group Console
 * @group SymfonyMessengerConsumeMessagesConsoleTest
 * Add your own group annotations below this line
 */
class SymfonyMessengerConsumeMessagesConsoleTest extends Unit
{
    /**
     * @var array<string>
     */
    protected const array SHARED_RECEIVERS = ['scheduler-jobs'];

    /**
     * A dedicated list adds a worker rather than consuming one of the shared ones. Without this the
     * receivers passed as arguments would be left with no worker whenever the counts happened to match,
     * and the dedicated assignment would be dropped in silence.
     *
     * @dataProvider workerReceiverAssignmentDataProvider
     *
     * @param array<int, array<string>> $dedicatedReceiverLists
     * @param array<int, array<string>> $expectedAssignments
     */
    public function testAssignsDedicatedWorkersOnTopOfTheSharedOnes(
        array $dedicatedReceiverLists,
        int $numberOfSharedProcesses,
        array $expectedAssignments
    ): void {
        // Arrange
        $console = new SymfonyMessengerConsumeMessagesConsole();
        $resolveWorkerReceivers = new ReflectionMethod($console, 'resolveWorkerReceivers');

        // Act
        $assignments = $resolveWorkerReceivers->invoke(
            $console,
            static::SHARED_RECEIVERS,
            $dedicatedReceiverLists,
            $numberOfSharedProcesses,
        );

        // Assert
        $this->assertSame($expectedAssignments, $assignments);
    }

    /**
     * @return array<string, mixed>
     */
    public function workerReceiverAssignmentDataProvider(): array
    {
        return [
            'one dedicated list and the default single shared worker' => [
                [['compiled-cron-scheduler']],
                1,
                [1 => ['compiled-cron-scheduler'], 2 => ['scheduler-jobs']],
            ],
            'one dedicated list scaled with --parallel' => [
                [['compiled-cron-scheduler']],
                4,
                [
                    1 => ['compiled-cron-scheduler'],
                    2 => ['scheduler-jobs'],
                    3 => ['scheduler-jobs'],
                    4 => ['scheduler-jobs'],
                    5 => ['scheduler-jobs'],
                ],
            ],
            'two dedicated lists' => [
                [['compiled-cron-scheduler'], ['other-group']],
                1,
                [1 => ['compiled-cron-scheduler'], 2 => ['other-group'], 3 => ['scheduler-jobs']],
            ],
            'no dedicated list behaves exactly as before' => [
                [],
                3,
                [1 => ['scheduler-jobs'], 2 => ['scheduler-jobs'], 3 => ['scheduler-jobs']],
            ],
            'dedicated only, shared workers explicitly switched off' => [
                [['compiled-cron-scheduler']],
                0,
                [1 => ['compiled-cron-scheduler']],
            ],
        ];
    }
}
