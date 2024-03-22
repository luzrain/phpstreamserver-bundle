<?php

declare(strict_types=1);

namespace Luzrain\PHPStreamServerBundle\Test;

use Revolt\EventLoop\Driver\StreamSelectDriver;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProcessTest extends KernelTestCase
{
    protected function tearDown(): void
    {
        if (\is_file($file = $this->getContainer()->getParameter('process_status_file'))) {
            \unlink($file);
        }
    }

    public function testProcessIsLive(): void
    {
        $content = $this->getProcessStatusFileContent() ?? $this->fail('Process status file is not found');

        $this->assertTrue((int) $content > \time() - 4, 'Process started more than 4 seconds ago');
    }

    private function getProcessStatusFileContent(): string|null
    {
        $file = $this->getContainer()->getParameter('process_status_file');
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
