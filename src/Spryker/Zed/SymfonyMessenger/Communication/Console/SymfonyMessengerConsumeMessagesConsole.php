<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \Spryker\Zed\SymfonyMessenger\Business\SymfonyMessengerFacadeInterface getFacade()
 * @method \Spryker\Zed\SymfonyMessenger\Communication\SymfonyMessengerCommunicationFactory getFactory()
 * @method \Spryker\Zed\SymfonyMessenger\SymfonyMessengerConfig getConfig()
 */
class SymfonyMessengerConsumeMessagesConsole extends Console
{
    /**
     * @var string
     */
    public const string COMMAND_NAME = 'symfonymessenger:consume';

    /**
     * @var string
     */
    public const string COMMAND_DESCRIPTION = 'Consume messages from Symfony Messenger transports';

    /**
     * @var string
     */
    public const string ARGUMENT_RECEIVERS = 'receivers';

    /**
     * @var string
     */
    public const string OPTION_LIMIT = 'limit';

    /**
     * @var string
     */
    public const string OPTION_FAILURE_LIMIT = 'failure-limit';

    /**
     * @var string
     */
    public const string OPTION_MEMORY_LIMIT = 'memory-limit';

    /**
     * @var string
     */
    public const string OPTION_TIME_LIMIT = 'time-limit';

    /**
     * @var string
     */
    public const string OPTION_SLEEP = 'sleep';

    /**
     * @var string
     */
    public const string OPTION_BUS = 'bus';

    /**
     * @var string
     */
    public const string OPTION_QUEUES = 'queues';

    /**
     * @var string
     */
    public const string OPTION_EXCLUDE_FROM_GROUP = 'exclude-from-group';

    protected function configure(): void
    {
        $this->setName(static::COMMAND_NAME);
        $this->setDescription(static::COMMAND_DESCRIPTION);

        $this->addArgument(
            static::ARGUMENT_RECEIVERS,
            InputArgument::IS_ARRAY | InputArgument::REQUIRED,
            'Names of the receivers/transports to consume in order of priority',
        );

        $this->addOption(
            static::OPTION_TIME_LIMIT,
            't',
            InputOption::VALUE_REQUIRED,
            'The time limit in seconds the worker can handle new messages',
        );

        $this->addOption(
            static::OPTION_SLEEP,
            null,
            InputOption::VALUE_REQUIRED,
            'Seconds to sleep before asking for new messages after no messages were found',
            1,
        );

        $this->addOption(
            static::OPTION_BUS,
            'b',
            InputOption::VALUE_REQUIRED,
            'Name of the bus to which received messages should be dispatched. Can be used if Envelope was formed without bus information.',
        );

        $this->addOption(
            static::OPTION_QUEUES,
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Limit receivers to only consume from the specified queues. Will work only with transports that support queue names (e.g. AMQP).',
        );

        $this->addOption(
            static::OPTION_EXCLUDE_FROM_GROUP,
            'e',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Exclude receivers from consuming if they belong to the provided group.',
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var array<string> $receivers */
        $receivers = $input->getArgument(static::ARGUMENT_RECEIVERS);

        if (!$receivers) {
            $this->error('Please provide at least one receiver name.');

            return static::CODE_ERROR;
        }

        $this->info(sprintf(
            'Starting to consume messages from receiver%s: %s',
            count($receivers) > 1 ? 's' : '',
            implode(', ', $receivers),
        ));

        $options = $this->buildConsumeOptions($input, $output);

        $this->getFactory()->createSymfonyMessengerConsumer()->consume($receivers, $options);

        return static::CODE_SUCCESS;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return array<string, mixed>
     */
    protected function buildConsumeOptions(InputInterface $input, OutputInterface $output): array
    {
        $options = [];

        if ($input->getOption(static::OPTION_TIME_LIMIT)) {
            $options['time-limit'] = (int)$input->getOption(static::OPTION_TIME_LIMIT);
        }

        if ($input->getOption(static::OPTION_SLEEP)) {
            $options['sleep'] = (int)$input->getOption(static::OPTION_SLEEP) * 1000000; // Convert to microseconds
        }

        if ($input->getOption(static::OPTION_BUS)) {
            $options['bus'] = $input->getOption(static::OPTION_BUS);
        }

        if ($input->getOption(static::OPTION_QUEUES)) {
            $options['queues'] = $input->getOption(static::OPTION_QUEUES);
        }

        if ($input->getOption(static::OPTION_EXCLUDE_FROM_GROUP)) {
            $options['exclude'] = $input->getOption(static::OPTION_EXCLUDE_FROM_GROUP);
        }

        $options['output'] = $output;

        return $options;
    }
}
