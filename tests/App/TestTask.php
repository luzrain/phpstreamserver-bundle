<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\Test\App;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:test_task',
    description: 'test task',
)]
final class TestTask extends Command
{
    public function __construct(
        #[Autowire(param: 'task_status_file')]
        private string $statusFile,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        \file_put_contents($this->statusFile, \time());

        return Command::SUCCESS;
    }
}
