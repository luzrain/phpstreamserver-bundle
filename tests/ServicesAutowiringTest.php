<?php

declare(strict_types=1);

namespace Luzrain\PHPStreamServerBundle\Test;

use Luzrain\PHPStreamServerBundle\Http\HttpRequestHandler;
use Luzrain\PHPStreamServerBundle\Internal\WorkerConfigurator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;

final class ServicesAutowiringTest extends KernelTestCase
{
    public function testServiceAutowiring(): void
    {
        $container = self::getContainer();

        $this->assertInstanceOf(HttpRequestHandler::class, $container->get('phprunner.http_request_handler'));
        $this->assertInstanceOf(WorkerConfigurator::class, $container->get('phprunner.worker_configurator'));
        $this->assertInstanceOf(Application::class, $container->get('phprunner.application'));
    }
}
