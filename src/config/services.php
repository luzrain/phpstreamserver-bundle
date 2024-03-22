<?php

declare(strict_types=1);

use Luzrain\PHPStreamServerBundle\ConfigLoader;
use Luzrain\PHPStreamServerBundle\Event\HttpServerStartEvent;
use Luzrain\PHPStreamServerBundle\Http\HttpRequestHandler;
use Luzrain\PHPStreamServerBundle\Internal\WorkerConfigurator;
use Luzrain\PHPStreamServerBundle\ReloadStrategy\OnEachRequest;
use Luzrain\PHPStreamServerBundle\ReloadStrategy\OnException;
use Luzrain\PHPStreamServerBundle\ReloadStrategy\OnMemoryLimit;
use Luzrain\PHPStreamServerBundle\ReloadStrategy\OnRequestsLimit;
use Luzrain\PHPStreamServerBundle\ReloadStrategy\OnTTLLimit;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

return static function (array $config, ContainerBuilder $container) {
    $container
        ->register('phprunner.config_loader', ConfigLoader::class)
        ->addMethodCall('setConfig', [$config])
        ->addTag('kernel.cache_warmer')
        ->setArguments([
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('kernel.cache_dir'),
            $container->getParameter('kernel.debug'),
        ])
    ;

    $container
        ->register('phprunner.http_request_handler', HttpRequestHandler::class)
        ->setArguments([new Reference('kernel')])
        ->setPublic(true)
    ;

    $container
        ->register('phprunner.worker_configurator', WorkerConfigurator::class)
        ->setArguments([new Reference('kernel'), new Reference('logger')])
        ->setPublic(true)
    ;

    $container
        ->register('phprunner.application', Application::class)
        ->addMethodCall('setAutoExit', [false])
        ->setArguments([new Reference('kernel')])
        ->setShared(false)
        ->setPublic(true)
    ;

    if ($config['reload_strategy']['on_exception']['active']) {
        $container
            ->register('phprunner.on_exception_reload_strategy', OnException::class)
            ->addTag('kernel.event_listener', [
                'event' => HttpServerStartEvent::class,
                'method' => 'onServerStart',
            ])
            ->addTag('kernel.event_listener', [
                'event' => ExceptionEvent::class,
                'method' => 'onException',
                'priority' => -100,
            ])
            ->setArguments([
                $config['reload_strategy']['on_exception']['allowed_exceptions'],
            ])
        ;
    }

    if ($config['reload_strategy']['on_each_request']['active']) {
        $container
            ->register('phprunner.on_each_request_reload_strategy', OnEachRequest::class)
            ->addTag('kernel.event_listener', [
                'event' => HttpServerStartEvent::class,
                'method' => 'onServerStart',
            ])
        ;
    }

    if ($config['reload_strategy']['on_ttl_limit']['active']) {
        $container
            ->register('phprunner.on_ttl_limit_reload_strategy', OnTTLLimit::class)
            ->addTag('kernel.event_listener', [
                'event' => HttpServerStartEvent::class,
                'method' => 'onServerStart',
            ])
            ->setArguments([
                $config['reload_strategy']['on_ttl_limit']['ttl'],
            ])
        ;
    }

    if ($config['reload_strategy']['on_requests_limit']['active']) {
        $container
            ->register('phprunner.on_requests_limit_reload_strategy', OnRequestsLimit::class)
            ->addTag('kernel.event_listener', [
                'event' => HttpServerStartEvent::class,
                'method' => 'onServerStart',
            ])
            ->setArguments([
                $config['reload_strategy']['on_requests_limit']['requests'],
                $config['reload_strategy']['on_requests_limit']['dispersion'],
            ])
        ;
    }

    if ($config['reload_strategy']['on_memory_limit']['active']) {
        $container
            ->register('phprunner.on_on_memory_limit_reload_strategy', OnMemoryLimit::class)
            ->addTag('kernel.event_listener', [
                'event' => HttpServerStartEvent::class,
                'method' => 'onServerStart',
            ])
            ->setArguments([
                $config['reload_strategy']['on_memory_limit']['memory'],
            ])
        ;
    }
};
