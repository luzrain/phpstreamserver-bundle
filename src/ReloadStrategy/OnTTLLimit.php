<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\ReloadStrategy;

use Luzrain\PhpRunner\ReloadStrategy\TTLReloadStrategy;
use Luzrain\PhpRunnerBundle\Event\HttpServerStartEvent;

final class OnTTLLimit extends TTLReloadStrategy
{
    public function onServerStart(HttpServerStartEvent $event): void
    {
        $event->worker->addReloadStrategies($this);
    }
}
