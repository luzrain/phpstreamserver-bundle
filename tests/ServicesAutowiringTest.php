<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\Test;

use Luzrain\PhpRunnerBundle\Http\HttpRequestHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ServicesAutowiringTest extends KernelTestCase
{
    public function testServiceAutowiring(): void
    {
        $container = self::getContainer();

        $this->assertInstanceOf(HttpRequestHandler::class, $container->get('phprunner.http_request_handler'));
    }
}
