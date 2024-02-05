<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\ReloadStrategy;

use Luzrain\PhpRunner\ReloadStrategy\MaxMemoryReloadStrategy;
use Luzrain\PhpRunnerBundle\Event\HttpServerStartEvent;

final class OnMemoryLimit extends MaxMemoryReloadStrategy
{
    public function onServerStart(HttpServerStartEvent $event): void
    {
        $event->worker->addReloadStrategies($this);
    }
}
