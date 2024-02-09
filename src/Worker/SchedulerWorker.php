<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\Worker;

use Luzrain\PhpRunner\WorkerProcess;
use Luzrain\PhpRunnerBundle\KernelFactory;
use Luzrain\PhpRunnerBundle\Scheduler\Trigger\TriggerFactory;
use Luzrain\PhpRunnerBundle\Scheduler\Trigger\TriggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

final class SchedulerWorker extends WorkerProcess
{
    private array $runningTaskMap = [];
    private array $externalProcessesMap = [];
    private KernelInterface $kernel;

    public function __construct(
        private readonly KernelFactory $kernelFactory,
        string|null $user,
        string|null $group,
        /** @var array{name: string, schedule: string, jitter: int, command: string} $tasks */
        private readonly array $tasks,
    ) {
        parent::__construct(
            name: 'Scheduler',
            user: $user,
            group: $group,
            onStart: $this->onStart(...),
            onStop: $this->onStop(...),
        );
    }

    private function onStart(): void
    {
        $this->kernel = $this->kernelFactory->createKernel();
        $this->kernel->boot();
        $this->kernel->getContainer()->get('phprunner.worker_configurator')->configure($this);

        foreach ($this->tasks as ['name' => $name, 'schedule' => $schedule, 'jitter' => $jitter, 'command' => $command]) {
            $name ??= $command;
            try {
                $trigger = TriggerFactory::create($schedule, $jitter);
            } catch (\InvalidArgumentException) {
                $this->getLogger()->warning(sprintf('Task "%s" skipped. Schedule "%s" is incorrect', $name, $schedule));
                continue;
            }

            $this->getLogger()->info(sprintf('Task "%s" scheduled. Schedule: "%s"', $name, $trigger));
            $this->scheduleCommand($trigger, $name, $command);
        }

        $this->getEventLoop()->onSignal(SIGCHLD, function () {
            while (($pid = \pcntl_wait($status, WNOHANG)) > 0) {
               $this->onChildProcessExit($pid);
            }
        });
    }

    private function onStop(): void
    {
        foreach ($this->runningTaskMap as $pid => $hash) {
            \posix_kill($pid, SIGTERM);
        }
        foreach ($this->runningTaskMap as $pid => $hash) {
            \pcntl_waitpid($pid, $status);
        }
    }

    private function onChildProcessExit(int $pid): void
    {
        if (isset($this->externalProcessesMap[$pid])) {
            \proc_close($this->externalProcessesMap[$pid]);
            unset($this->externalProcessesMap[$pid]);
        }
        if (isset($this->runningTaskMap[$pid])) {
            unset($this->runningTaskMap[$pid]);
        }
    }

    private function scheduleCommand(TriggerInterface $trigger, string $name, string $command): void
    {
        $currentDate = new \DateTimeImmutable();
        $nextRunDate = $trigger->getNextRunDate($currentDate);
        if ($nextRunDate !== null) {
            $interval = $nextRunDate->getTimestamp() - $currentDate->getTimestamp();
            $this->getEventLoop()->delay($interval, fn () => $this->runCommand($trigger, $name, $command));
        }
    }

    private function runCommand(TriggerInterface $trigger, string $name, string $command): void
    {
        // Reschedule task without running it if previous task is still running
        if (\in_array($taskHash = \hash('xxh64', $trigger . $name . $command), $this->runningTaskMap)) {
            $this->scheduleCommand($trigger, $name, $command);
            return;
        }

        $application = new Application($this->kernel);

        if ($application->has($command)) {
            // If command is symfony console command execute it in a forked process
            if (-1 === $pid = $this->executeSymfonyCommand($application, $command)) {
                $this->getLogger()->error(sprintf('Task "%s" call error!', $name));
            } else {
                $this->getLogger()->info(sprintf('Task "%s" called', $name));
                $this->runningTaskMap[$pid] = $taskHash;
                $this->scheduleCommand($trigger, $name, $command);
            }
        } else {
            $this->getLogger()->info(sprintf('Task "%s" called', $name));
            $pid = $this->executeExternalCommand($command, function (string $error) use ($name) {
                $this->getLogger()->error(sprintf('Task "%s" call error!', $name), ['error' => $error]);
            });
            $this->runningTaskMap[$pid] = $taskHash;
            $this->scheduleCommand($trigger, $name, $command);
        }
    }

    private function executeSymfonyCommand(Application $application, string $command): int
    {
        if (0 !== $pid = \pcntl_fork()) {
            return $pid;
        }

        // Execute in a forked process
        $identifiers = $this->getEventLoop()->getIdentifiers();
        \array_walk($identifiers, $this->getEventLoop()->disable(...));
        $this->getEventLoop()->stop();
        \cli_set_process_title($command);
        \pcntl_signal(SIGINT, SIG_IGN);

        $application->setAutoExit(false);
        $input = new StringInput($command);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        exit($application->run($input, $output));
    }

    /**
     * @param null|\Closure(string): void $onStdErrorCallback
     */
    private function executeExternalCommand(string $command, \Closure|null $onStdErrorCallback = null): int
    {
        $cwd = $this->kernel->getProjectDir();
        $envVars = [...\getenv(), ...$_ENV];
        $socketPair = \stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        \stream_set_blocking($socketPair[1], false);
        $descriptorspec = [
            1 => ['file', '/dev/null', 'w'],
            2 => $socketPair[0],
        ];
        $process = \proc_open("exec $command", $descriptorspec, $pipes, $cwd, $envVars);
        $pid = \proc_get_status($process)['pid'];
        $this->externalProcessesMap[$pid] = &$process;

        if ($onStdErrorCallback !== null) {
            $errorCallbackId = $this->getEventLoop()->onReadable($socketPair[1], function (string $id, mixed $fd) use ($onStdErrorCallback): void {
                \feof($fd) ? $this->getEventLoop()->disable($id) : $onStdErrorCallback(\trim(\stream_get_contents($fd)));
            });
            $this->getEventLoop()->delay(1, function () use ($errorCallbackId, $process): void {
                $this->getEventLoop()->cancel($errorCallbackId);
            });
        }

        return $pid;
    }
}
