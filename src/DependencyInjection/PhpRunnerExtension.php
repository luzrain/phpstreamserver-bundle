<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\DependencyInjection;

use Luzrain\PhpRunnerBundle\Attribute\AsProcess;
use Luzrain\PhpRunnerBundle\Attribute\AsTask;
use Luzrain\PhpRunnerBundle\Command\ReloadCommand;
use Luzrain\PhpRunnerBundle\Command\StartCommand;
use Luzrain\PhpRunnerBundle\Command\StatusCommand;
use Luzrain\PhpRunnerBundle\Command\StopCommand;
use Luzrain\PhpRunnerBundle\ConfigLoader;
use Luzrain\PhpRunnerBundle\KernelRunner;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\KernelInterface;

final class PhpRunnerExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
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

//        $container
//            ->register('workerman.workerman_http_message_factory', WorkermanHttpMessageFactory::class)
//            ->setArguments([
//                new Reference(ServerRequestFactoryInterface::class),
//                new Reference(StreamFactoryInterface::class),
//                new Reference(UploadedFileFactoryInterface::class),
//            ])
//        ;
//
//        $container
//            ->register('workerman.task_error_listener', TaskErrorListener::class)
//            ->addTag('kernel.event_subscriber')
//            ->addTag('monolog.logger', ['channel' => 'task'])
//            ->setArguments([
//                new Reference('logger'),
//            ])
//        ;
//
//        $container
//            ->register('workerman.process_error_listener', ProcessErrorListener::class)
//            ->addTag('kernel.event_subscriber')
//            ->addTag('monolog.logger', ['channel' => 'process'])
//            ->setArguments([
//                new Reference('logger'),
//            ])
//        ;
//
//        $container
//            ->register('workerman.kernel_runner', KernelRunner::class)
//            ->setArguments([new Reference(KernelInterface::class)])
//        ;
//
//        $container->registerAttributeForAutoconfiguration(AsProcess::class, $this->processConfig(...));
//        $container->registerAttributeForAutoconfiguration(AsTask::class, $this->taskConfig(...));
//
//        if ($config['reload_strategy']['always']['active']) {
//            $container
//                ->register('workerman.always_reboot_strategy', AlwaysRebootStrategy::class)
//                ->addTag('workerman.reboot_strategy')
//            ;
//        }
//
//        if ($config['reload_strategy']['max_requests']['active']) {
//            $container
//                ->register('workerman.max_requests_reboot_strategy', MaxJobsRebootStrategy::class)
//                ->addTag('workerman.reboot_strategy')
//                ->setArguments([
//                    $config['reload_strategy']['max_requests']['requests'],
//                    $config['reload_strategy']['max_requests']['dispersion'],
//                ])
//            ;
//        }
//
//        if ($config['reload_strategy']['exception']['active']) {
//            $container
//                ->register('workerman.exception_reboot_strategy', ExceptionRebootStrategy::class)
//                ->addTag('workerman.reboot_strategy')
//                ->addTag('kernel.event_listener', [
//                    'event' => 'kernel.exception',
//                    'priority' => -100,
//                    'method' => 'onException',
//                ])
//                ->setArguments([
//                    $config['reload_strategy']['exception']['allowed_exceptions'],
//                ])
//            ;
//        }
    }

//    private function processConfig(ChildDefinition $definition, AsProcess $attribute): void
//    {
//        $definition->addTag('workerman.process', [
//            'name' => $attribute->name,
//            'processes' => $attribute->processes,
//            'method' => $attribute->method,
//        ]);
//    }
//
//    private function taskConfig(ChildDefinition $definition, AsTask $attribute): void
//    {
//        $definition->addTag('workerman.task', [
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
