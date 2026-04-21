<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\SymfonyMessenger\Config;

use Codeception\Test\Unit;
use Spryker\Client\SymfonyMessenger\SymfonyMessengerConfig;
use Spryker\Shared\SymfonyMessenger\SymfonyMessengerConstants;
use SprykerTest\Client\SymfonyMessenger\SymfonyMessengerClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group SymfonyMessenger
 * @group Config
 * @group SymfonyMessengerConfigTest
 * Add your own group annotations below this line
 */
class SymfonyMessengerConfigTest extends Unit
{
    protected SymfonyMessengerClientTester $tester;

    public function testGetAmqpConnectionDsnBuildsCorrectDsnFromIndividualConstants(): void
    {
        // Arrange
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_HOST, 'localhost');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_PORT, '5672');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_USERNAME, 'guest');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_PASSWORD, 'guest');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_VIRTUAL_HOST, '/eu-docker');

        // Act
        $dsn = (new SymfonyMessengerConfig())->getAmqpConnectionDSN();

        // Assert
        $this->assertSame('amqp://guest:guest@localhost:5672/eu-docker', $dsn);
    }

    public function testGetAmqpConnectionDsnEncodesPasswordWithSpecialCharacters(): void
    {
        // Arrange
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_HOST, 'localhost');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_PORT, '5672');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_USERNAME, 'spryker');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_PASSWORD, 'p@ss/w0rd!');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_VIRTUAL_HOST, '/eu-docker');

        // Act
        $dsn = (new SymfonyMessengerConfig())->getAmqpConnectionDSN();

        // Assert
        $this->assertSame('amqp://spryker:p%40ss%2Fw0rd%21@localhost:5672/eu-docker', $dsn);
    }

    public function testGetAmqpConnectionDsnStripsLeadingSlashFromVirtualHost(): void
    {
        // Arrange
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_HOST, 'localhost');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_PORT, '5672');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_USERNAME, 'guest');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_PASSWORD, 'guest');
        $this->tester->setConfig(SymfonyMessengerConstants::QUEUE_AMQP_VIRTUAL_HOST, '/my-vhost');

        // Act
        $dsn = (new SymfonyMessengerConfig())->getAmqpConnectionDSN();

        // Assert
        $this->assertStringEndsWith('/my-vhost', $dsn);
    }
}
