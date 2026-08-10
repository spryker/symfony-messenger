<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SymfonyMessenger\Communication\Process;

use Codeception\Test\Unit;
use Spryker\Zed\SymfonyMessenger\Communication\Process\ProcessPool;
use SprykerTest\Zed\SymfonyMessenger\SymfonyMessengerZedTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group SymfonyMessenger
 * @group Communication
 * @group Process
 * @group ProcessPoolTest
 * Add your own group annotations below this line
 */
class ProcessPoolTest extends Unit
{
    protected const int CODE_SUCCESS = 0;

    protected const int CODE_ERROR = 1;

    protected SymfonyMessengerZedTester $tester;

    public function testRunReturnsSuccessWhenAllChildProcessesSucceed(): void
    {
        // Arrange
        $processPool = new ProcessPool();
        $command = [PHP_BINARY, '-r', 'usleep(10000);'];

        // Act
        $exitCode = $processPool->run([1 => $command, 2 => $command, 3 => $command]);

        // Assert
        $this->assertSame(static::CODE_SUCCESS, $exitCode);
    }

    public function testRunReturnsErrorWhenAnyChildProcessFails(): void
    {
        // Arrange
        $processPool = new ProcessPool();
        $successCommand = [PHP_BINARY, '-r', 'usleep(10000);'];
        $failingCommand = [PHP_BINARY, '-r', 'exit(3);'];

        // Act
        $exitCode = $processPool->run([1 => $successCommand, 2 => $failingCommand]);

        // Assert
        $this->assertSame(static::CODE_ERROR, $exitCode);
    }
}
