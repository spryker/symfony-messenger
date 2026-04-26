<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\SymfonyMessenger\Communication\Plugin\Queue;

use Codeception\Test\Unit;
use Spryker\Client\SymfonyMessenger\Adapter\SymfonyMessengerQueueAdapter;
use Spryker\Zed\SymfonyMessenger\Communication\Plugin\Queue\SymfonyMessengerQueueMetricsReaderPlugin;
use SprykerTest\Zed\SymfonyMessenger\SymfonyMessengerZedTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group SymfonyMessenger
 * @group Communication
 * @group Plugin
 * @group Queue
 * @group SymfonyMessengerQueueMetricsReaderPluginTest
 * Add your own group annotations below this line
 */
class SymfonyMessengerQueueMetricsReaderPluginTest extends Unit
{
    protected SymfonyMessengerZedTester $tester;

    public function testIsApplicableReturnsTrueForSymfonyMessengerAdapter(): void
    {
        // Arrange
        $plugin = new SymfonyMessengerQueueMetricsReaderPlugin();

        // Act
        $result = $plugin->isApplicable(SymfonyMessengerQueueAdapter::class);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsApplicableReturnsFalseForOtherAdapter(): void
    {
        // Arrange
        $plugin = new SymfonyMessengerQueueMetricsReaderPlugin();

        // Act
        $result = $plugin->isApplicable('Spryker\Client\RabbitMq\Model\RabbitMqAdapter');

        // Assert
        $this->assertFalse($result);
    }
}
