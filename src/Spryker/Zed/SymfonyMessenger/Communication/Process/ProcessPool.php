<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\SymfonyMessenger\Communication\Process;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessPool implements ProcessPoolInterface
{
    /**
     * @var array<int>
     */
    protected const array HANDLED_SIGNALS = [SIGTERM, SIGINT];

    protected const int POLL_INTERVAL_MICROSECONDS = 100000;

    protected const int STOP_TIMEOUT_SECONDS = 10;

    protected const string OUTPUT_PREFIX_FORMAT = '[worker-%d] %s';

    protected const int CODE_SUCCESS = 0;

    protected const int CODE_ERROR = 1;

    /**
     * @param array<int, array<string>> $commands
     * @param array<string, mixed> $options
     */
    public function run(array $commands, array $options = []): int
    {
        /** @var \Symfony\Component\Console\Output\OutputInterface|null $output */
        $output = $options['output'] ?? null;

        $processes = $this->startProcesses($commands, $output);
        $previousHandlers = $this->installSignalHandlers($processes);

        try {
            $this->waitForProcesses($processes);
        } finally {
            $this->restoreSignalHandlers($previousHandlers);
        }

        return $this->resolveExitCode($processes);
    }

    /**
     * @param array<int, array<string>> $commands
     *
     * @return array<int, \Symfony\Component\Process\Process>
     */
    protected function startProcesses(array $commands, ?OutputInterface $output): array
    {
        $processes = [];
        foreach ($commands as $workerNumber => $command) {
            $process = new Process($command);
            // Worker processes are long-running; the parent must not impose its own timeout.
            $process->setTimeout(null);

            $process->start(function (string $type, string $buffer) use ($workerNumber, $output): void {
                $this->writePrefixedOutput($output, $workerNumber, $buffer);
            });

            $processes[$workerNumber] = $process;
            $output?->writeln(sprintf('Started worker #%d.', $workerNumber));
        }

        return $processes;
    }

    /**
     * @param array<int, \Symfony\Component\Process\Process> $processes
     */
    protected function waitForProcesses(array $processes): void
    {
        do {
            $hasRunningProcess = false;
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $hasRunningProcess = true;
                }
            }

            if ($hasRunningProcess) {
                usleep(static::POLL_INTERVAL_MICROSECONDS);
            }
        } while ($hasRunningProcess);
    }

    protected function writePrefixedOutput(?OutputInterface $output, int $workerNumber, string $buffer): void
    {
        if ($output === null) {
            return;
        }

        foreach (explode(PHP_EOL, rtrim($buffer, PHP_EOL)) as $line) {
            if ($line === '') {
                continue;
            }

            $output->writeln(sprintf(static::OUTPUT_PREFIX_FORMAT, $workerNumber, $line));
        }
    }

    /**
     * @param array<int, \Symfony\Component\Process\Process> $processes
     *
     * @return array<int, callable|int|string>
     */
    protected function installSignalHandlers(array $processes): array
    {
        if (!function_exists('pcntl_async_signals') || !function_exists('pcntl_signal')) {
            return [];
        }

        pcntl_async_signals(true);

        $previousHandlers = [];
        foreach (static::HANDLED_SIGNALS as $signal) {
            $previousHandlers[$signal] = pcntl_signal_get_handler($signal);
        }

        foreach (static::HANDLED_SIGNALS as $signal) {
            $previousHandler = $previousHandlers[$signal];
            pcntl_signal($signal, function (int $receivedSignal) use ($processes, $previousHandler): void {
                $this->stopProcesses($processes);

                if (is_callable($previousHandler)) {
                    // Signal handlers registered via pcntl_signal receive the signal number, but the pcntl_signal_get_handler stub types them as zero-argument callables.
                    /** @phpstan-ignore arguments.count */
                    $previousHandler($receivedSignal);
                }
            });
        }

        return $previousHandlers;
    }

    /**
     * @param array<int, \Symfony\Component\Process\Process> $processes
     */
    protected function stopProcesses(array $processes): void
    {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop(static::STOP_TIMEOUT_SECONDS, SIGTERM);
            }
        }
    }

    /**
     * @param array<int, callable|int|string> $previousHandlers
     */
    protected function restoreSignalHandlers(array $previousHandlers): void
    {
        if (!function_exists('pcntl_signal')) {
            return;
        }

        foreach ($previousHandlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }
    }

    /**
     * @param array<int, \Symfony\Component\Process\Process> $processes
     */
    protected function resolveExitCode(array $processes): int
    {
        foreach ($processes as $process) {
            if ($process->getExitCode() !== static::CODE_SUCCESS) {
                return static::CODE_ERROR;
            }
        }

        return static::CODE_SUCCESS;
    }
}
