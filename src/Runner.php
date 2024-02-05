<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle;

use Luzrain\PhpRunner\PhpRunner;
use Luzrain\PhpRunnerBundle\Worker\HttpServerWorker;
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

        // Warm up cache if no phprunner fresh config found (do it in a forked process as the main process should not boot kernel)
        if (!$configLoader->isFresh()) {
            $configLoader->warmUpInFork($this->kernelFactory);
        }

        $config = $configLoader->getConfig();
        //$schedulerConfig = $configLoader->getSchedulerConfig();
        //$processConfig = $configLoader->getProcessConfig();

        if (!\is_dir($varRunDir = \dirname($config['pid_file']))) {
            \mkdir(directory: $varRunDir, recursive: true);
        }

        $phpRunner = new PhpRunner();

        foreach ($config['servers'] as $serverConfig) {
            $phpRunner->addWorkers(new HttpServerWorker(
                kernelFactory: $this->kernelFactory,
                listen: $serverConfig['listen'],
                localCert: $serverConfig['local_cert'],
                localPk: $serverConfig['local_pk'],
                name: $serverConfig['name'],
                count: $serverConfig['processes'],
                user: $config['user'],
                group: $config['group'],
            ));
        }

        return $phpRunner->run();


        if (!empty($schedulerConfig)) {
            new SchedulerWorker(
                kernelFactory: $this->kernelFactory,
                user: $config['user'],
                group: $config['group'],
                schedulerConfig: $schedulerConfig,
            );
        }

        if ($config['reload_strategy']['file_monitor']['active'] && $this->kernelFactory->isDebug()) {
            new FileMonitorWorker(
                user: $config['user'],
                group: $config['group'],
                sourceDir: $config['reload_strategy']['file_monitor']['source_dir'],
                filePattern: $config['reload_strategy']['file_monitor']['file_pattern'],
            );
        }

        if (!empty($processConfig)) {
            new SupervisorWorker(
                kernelFactory: $this->kernelFactory,
                user: $config['user'],
                group: $config['group'],
                processConfig: $processConfig,
            );
        }


        return 0;
    }
}
