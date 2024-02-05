<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\ReloadStrategy;

use Luzrain\PhpRunner\ReloadStrategy\MaxRequestsReloadStrategy;
use Luzrain\PhpRunnerBundle\Event\HttpServerStartEvent;

final class OnRequestsLimit extends MaxRequestsReloadStrategy
{
    public function onServerStart(HttpServerStartEvent $event): void
    {
        $event->worker->addReloadStrategies($this);
    }
}
