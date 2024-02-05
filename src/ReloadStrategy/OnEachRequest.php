<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\ReloadStrategy;

use Luzrain\PhpRunner\ReloadStrategy\EachRequestReloadStrategy;
use Luzrain\PhpRunnerBundle\Event\HttpServerStartEvent;

final class OnEachRequest extends EachRequestReloadStrategy
{
    public function onServerStart(HttpServerStartEvent $event): void
    {
        $event->worker->addReloadStrategies($this);
    }
}
