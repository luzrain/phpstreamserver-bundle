<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle;

use Luzrain\PhpRunner\PhpRunner;
use Luzrain\PhpRunnerBundle\Internal\Functions;
use Luzrain\PhpRunnerBundle\Worker\HttpServerWorker;
use Luzrain\PhpRunnerBundle\Worker\ProcessWorker;
use Luzrain\PhpRunnerBundle\Worker\SchedulerWorker;
use Symfony\Component\Runtime\RunnerInterface;

final readonly class Runner implements RunnerInterface
{
    public function __construct(private KernelFactory $kernelFactory)
    {
    }

    public function run(): int
    {
        $configLoader = new ConfigLoader(
            projectDir: $this->kernelFactory->getProjectDir(),
            cacheDir: $this->kernelFactory->getCacheDir(),
            isDebug: $this->kernelFactory->isDebug(),
        );

        $config = $configLoader->getConfig($this->kernelFactory);

        $phpRunner = new PhpRunner(
            pidFile: $config['pid_file'],
            stopTimeout: $config['stop_timeout'],
        );

        foreach ($config['servers'] as $serverConfig) {
            $phpRunner->addWorkers(new HttpServerWorker(
                kernelFactory: $this->kernelFactory,
                listen: $serverConfig['listen'],
                localCert: $serverConfig['local_cert'],
                localPk: $serverConfig['local_pk'],
                name: $serverConfig['name'] ?? 'Webserver',
                count: $serverConfig['processes'] ?? Functions::cpuCount() * 2,
                user: $config['user'],
                group: $config['group'],
                maxBodySize: $serverConfig['max_body_size'],
            ));
        }

        if (!empty($config['tasks'])) {
            $phpRunner->addWorkers(new SchedulerWorker(
                kernelFactory: $this->kernelFactory,
                user: $config['user'],
                group: $config['group'],
                tasks: $config['tasks'],
            ));
        }

        foreach ($config['processes'] as $processConfig) {
            $phpRunner->addWorkers(new ProcessWorker(
                kernelFactory: $this->kernelFactory,
                user: $config['user'],
                group: $config['group'],
                name: $processConfig['name'],
                command: $processConfig['command'],
                count: $processConfig['count'],
            ));
        }

        return $phpRunner->run();
    }
}
