<?php

declare(strict_types=1);

namespace Luzrain\PHPStreamServerBundle\Test;

use Revolt\EventLoop\Driver\StreamSelectDriver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TaskTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        if (\is_file($file = $this->getContainer()->getParameter('task_status_file'))) {
            \unlink($file);
        }
    }

    public function testTaskIsRunning(): void
    {
        $content = $this->getTaskStatusFileContent() ?? $this->fail('Task status file is not found');

        $this->assertTrue((int) $content > \time() - 4, 'Task was called more than 4 seconds ago');
    }

    private function getTaskStatusFileContent(): string|null
    {
        $file = $this->getContainer()->getParameter('task_status_file');
        $eventLoop = new StreamSelectDriver();
        $suspension = $eventLoop->getSuspension();
        $eventLoop->delay(3, fn() => $suspension->resume());
        $eventLoop->repeat(0.5, function () use ($file, $suspension) {
            if ((\file_exists($file) && $content = @\file_get_contents($file))) {
                $suspension->resume($content);
            }
        });

        return $suspension->suspend();
    }
}
