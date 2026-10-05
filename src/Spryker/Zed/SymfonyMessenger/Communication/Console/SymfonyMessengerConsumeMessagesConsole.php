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
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * @method \Spryker\Zed\SymfonyMessenger\Business\SymfonyMessengerFacadeInterface getFacade()
 * @method \Spryker\Zed\SymfonyMessenger\Communication\SymfonyMessengerCommunicationFactory getFactory()
 * @method \Spryker\Zed\SymfonyMessenger\SymfonyMessengerConfig getConfig()
 */
class SymfonyMessengerConsumeMessagesConsole extends Console
{
    public const string COMMAND_NAME = 'symfonymessenger:consume';

    public const string COMMAND_DESCRIPTION = 'Consume messages from Symfony Messenger transports';

    public const string ARGUMENT_RECEIVERS = 'receivers';

    public const string OPTION_TIME_LIMIT = 'time-limit';

    public const string OPTION_SLEEP = 'sleep';

    public const string OPTION_BUS = 'bus';

    public const string OPTION_QUEUES = 'queues';

    public const string OPTION_EXCLUDE_FROM_GROUP = 'exclude-from-group';

    public const string OPTION_PARALLEL = 'parallel';

    public const string OPTION_WORKER_RECEIVERS = 'worker-receivers';

    protected const string OPTION_OUTPUT = 'output';

    protected const int DEFAULT_PARALLEL_PROCESSES = 1;

    /**
     * @var array<string>
     */
    protected const array CHILD_VALUE_OPTIONS = [
        self::OPTION_TIME_LIMIT,
        self::OPTION_SLEEP,
        self::OPTION_BUS,
    ];

    /**
     * @var array<string>
     */
    protected const array CHILD_ARRAY_OPTIONS = [
        self::OPTION_QUEUES,
        self::OPTION_EXCLUDE_FROM_GROUP,
    ];

    protected const string FALLBACK_CONSOLE_SCRIPT = 'vendor/bin/console';

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

        $this->addOption(
            static::OPTION_PARALLEL,
            'p',
            InputOption::VALUE_REQUIRED,
            'Number of worker processes consuming the receivers passed as arguments. When it resolves to more than one child, '
            . 'the command spawns child processes of itself instead of consuming directly. Additive with --worker-receivers: '
            . 'each dedicated list gets its own child on top of these. '
            . 'Intended for work queues (e.g. AMQP), where the processes act as competing consumers. '
            . 'The scheduler transport is also safe to run in parallel: each scheduled job is guarded by the Lock facade in the cron jobs builder, so the same schedule is never executed by more than one worker at the same time.',
            static::DEFAULT_PARALLEL_PROCESSES,
        );

        $this->addOption(
            static::OPTION_WORKER_RECEIVERS,
            'w',
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Give one child process its own receiver list, as a comma-separated set of receiver names. '
            . 'Repeat the option to dedicate further children. Children without a dedicated list consume the '
            . 'receivers passed as arguments and act as competing consumers over them. '
            . 'Only has an effect when more than one process is spawned.',
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

        $dedicatedReceiverLists = $this->getDedicatedReceiverLists($input);
        $numberOfSharedProcesses = (int)$input->getOption(static::OPTION_PARALLEL);

        // A dedicated list can only be honoured by a child process, so any assignment forces the pool
        // even when it resolves to a single worker. Without this the option would be silently ignored.
        if ($dedicatedReceiverLists !== [] || $numberOfSharedProcesses > static::DEFAULT_PARALLEL_PROCESSES) {
            return $this->runInParallel($input, $output, $receivers, $dedicatedReceiverLists, $numberOfSharedProcesses);
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
     * @param array<string> $receivers
     * @param array<int, array<string>> $dedicatedReceiverLists
     */
    protected function runInParallel(
        InputInterface $input,
        OutputInterface $output,
        array $receivers,
        array $dedicatedReceiverLists,
        int $numberOfSharedProcesses
    ): int {
        $assignments = $this->resolveWorkerReceivers($receivers, $dedicatedReceiverLists, $numberOfSharedProcesses);

        $this->info(sprintf('Spawning %d parallel worker processes.', count($assignments)));

        $commands = [];
        foreach ($assignments as $workerNumber => $workerReceivers) {
            $this->info(sprintf('Worker #%d consumes: %s', $workerNumber, implode(', ', $workerReceivers)));
            $commands[$workerNumber] = $this->buildChildCommand($input, $workerReceivers);
        }

        return $this->getFactory()->createParallelProcessPool()->run(
            $commands,
            [static::OPTION_OUTPUT => $output],
        );
    }

    /**
     * Assigns receivers per child. Each --worker-receivers list gets a child of its own, and
     * --parallel then adds that many further children consuming the receivers passed as arguments, as
     * competing consumers. The two are additive, so a dedicated worker never displaces a shared one -
     * with `--worker-receivers=a b` and no --parallel the result is one child on `a` and one on `b`.
     *
     * @param array<string> $sharedReceivers
     * @param array<int, array<string>> $dedicatedReceiverLists
     *
     * @return array<int, array<string>>
     */
    protected function resolveWorkerReceivers(
        array $sharedReceivers,
        array $dedicatedReceiverLists,
        int $numberOfSharedProcesses
    ): array {
        $assignments = $dedicatedReceiverLists;

        for ($index = 0; $index < $numberOfSharedProcesses; $index++) {
            $assignments[] = $sharedReceivers;
        }

        /** @var array<int, array<string>> $numberedAssignments */
        $numberedAssignments = array_combine(range(1, count($assignments)), $assignments);

        return $numberedAssignments;
    }

    /**
     * @return array<int, array<string>>
     */
    protected function getDedicatedReceiverLists(InputInterface $input): array
    {
        $dedicatedReceiverLists = [];

        foreach ((array)$input->getOption(static::OPTION_WORKER_RECEIVERS) as $dedicatedReceiverList) {
            $dedicatedReceivers = array_values(array_filter(array_map('trim', explode(',', (string)$dedicatedReceiverList))));

            if ($dedicatedReceivers !== []) {
                $dedicatedReceiverLists[] = $dedicatedReceivers;
            }
        }

        return $dedicatedReceiverLists;
    }

    /**
     * Rebuilds this command's invocation for a child process, preserving all provided
     * options and receivers except the parallel option. Returned as a list of arguments
     * to be run without a shell.
     *
     * @param array<string> $receivers
     *
     * @return array<string>
     */
    protected function buildChildCommand(InputInterface $input, array $receivers): array
    {
        $parts = [
            $this->resolvePhpBinary(),
            $this->resolveConsoleScript(),
            static::COMMAND_NAME,
        ];

        foreach (static::CHILD_VALUE_OPTIONS as $option) {
            $value = $input->getOption($option);
            if ($value === null || $value === '') {
                continue;
            }

            $parts[] = sprintf('--%s=%s', $option, (string)$value);
        }

        foreach (static::CHILD_ARRAY_OPTIONS as $option) {
            foreach ((array)$input->getOption($option) as $value) {
                $parts[] = sprintf('--%s=%s', $option, (string)$value);
            }
        }

        foreach ($receivers as $receiver) {
            $parts[] = $receiver;
        }

        return $parts;
    }

    protected function resolvePhpBinary(): string
    {
        return (new PhpExecutableFinder())->find() ?: PHP_BINARY;
    }

    protected function resolveConsoleScript(): string
    {
        return $_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['argv'][0] ?? static::FALLBACK_CONSOLE_SCRIPT;
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

        $options[static::OPTION_OUTPUT] = $output;

        return $options;
    }
}
