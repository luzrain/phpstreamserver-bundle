<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\DependencyInjection;

use Luzrain\PhpRunnerBundle\ConfigLoader;
use Luzrain\PhpRunnerBundle\Event\HttpServerStartEvent;
use Luzrain\PhpRunnerBundle\Http\HttpRequestHandler;
use Luzrain\PhpRunnerBundle\ReloadStrategy\OnEachRequest;
use Luzrain\PhpRunnerBundle\ReloadStrategy\OnException;
use Luzrain\PhpRunnerBundle\ReloadStrategy\OnMemoryLimit;
use Luzrain\PhpRunnerBundle\ReloadStrategy\OnRequestsLimit;
use Luzrain\PhpRunnerBundle\ReloadStrategy\OnTTLLimit;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelInterface;

final class PhpRunnerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var ConfigurationInterface $configuration */
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

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
            ->setArguments([new Reference(KernelInterface::class)])
            ->setPublic(true)
        ;

        $container
            ->setAlias('phprunner.logger', 'logger')
            ->setPublic(true);

        if ($config['reload_strategy']['on_exception']['active']) {
            $container
                ->register('phprunner.on_exception_reload_strategy', OnException::class)
                ->addTag('kernel.event_listener', [
                    'event' => HttpServerStartEvent::class,
                    'method' => 'onServerStart',
                ])
                ->addTag('kernel.event_listener', [
                    'event' => ExceptionEvent::class,
                    'priority' => -100,
                    'method' => 'onException',
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

        //        $container->registerAttributeForAutoconfiguration(AsProcess::class, $this->processConfig(...));
        //        $container->registerAttributeForAutoconfiguration(AsTask::class, $this->taskConfig(...));
    }

    //    private function processConfig(ChildDefinition $definition, AsProcess $attribute): void
    //    {
    //        $definition->addTag('phprunner.process', [
    //            'name' => $attribute->name,
    //            'processes' => $attribute->processes,
    //            'method' => $attribute->method,
    //        ]);
    //    }
    //
    //    private function taskConfig(ChildDefinition $definition, AsTask $attribute): void
    //    {
    //        $definition->addTag('phprunner.task', [
    //            'name' => $attribute->name,
    //            'schedule' => $attribute->schedule,
    //            'method' => $attribute->method,
    //            'jitter' => $attribute->jitter,
    //        ]);
    //    }

    public function getAlias(): string
    {
        return 'phprunner';
    }
}
