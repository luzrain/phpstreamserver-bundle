<?php

declare(strict_types=1);

namespace Luzrain\PHPStreamServerBundle\Test\App;

use Luzrain\PHPStreamServerBundle\PHPStreamServerBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new PHPStreamServerBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) use ($loader) {
            $container->loadFromExtension('framework', [
                'test' => true,
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                ],
            ]);

            $container->register('kernel', self::class)
                ->addTag('controller.service_arguments')
                ->addTag('routing.route_loader')
            ;

            $container->loadFromExtension('phpstreamserver', [
                'servers' => [
                    [
                        'name' => 'Test server',
                        'listen' => 'http://127.0.0.1:8888',
                        'processes' => 1,
                    ],
                ],
                'tasks' => [
                    [
                        'command' => 'app:test_task',
                        'schedule' => '1 second',
                    ],
                ],
                'processes' => [
                    [
                        'command' => 'app:test_process',
                    ],
                ],
            ]);

            $container->setParameter('task_status_file', '%kernel.project_dir%/var/task_status.log');
            $container->setParameter('process_status_file', '%kernel.project_dir%/var/process_status.log');

            $container->autowire(RequestTestController::class)->setAutoconfigured(true);
            $container->autowire(TestTask::class)->setAutoconfigured(true);
            $container->autowire(TestProcess::class)->setAutoconfigured(true);
        });
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('*.php', 'attribute');
    }
}
