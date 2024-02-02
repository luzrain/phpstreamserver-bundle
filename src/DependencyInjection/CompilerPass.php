<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\DependencyInjection;

use Luzrain\PhpRunnerBundle\Http\HttpRequestHandler;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        //$tasks = array_map(fn(array $a) => $a[0], $container->findTaggedServiceIds('workerman.task'));
        //$processes = array_map(fn(array $a) => $a[0], $container->findTaggedServiceIds('workerman.process'));
        //$rebootStrategies = array_map(fn(array $a) => $a[0], $container->findTaggedServiceIds('workerman.reboot_strategy'));

//        $container
//            ->getDefinition('phprunner.config_loader')
//            ->addMethodCall('setProcessConfig', [$processes])
//            ->addMethodCall('setSchedulerConfig', [$tasks])
//        ;

//        $container
//            ->register('workerman.task_locator', ServiceLocator::class)
//            ->addTag('container.service_locator')
//            ->setArguments([$this->referenceMap($tasks)])
//        ;
//
//        $container
//            ->register('workerman.process_locator', ServiceLocator::class)
//            ->addTag('container.service_locator')
//            ->setArguments([$this->referenceMap($processes)])
//        ;
//
//        $container
//            ->register('workerman.reboot_strategy', StackRebootStrategy::class)
//            ->setArguments([$this->referenceMap($rebootStrategies)])
//        ;
//
        $container
            ->register('phprunner.http_request_handler', HttpRequestHandler::class)
            ->setArguments([new Reference(KernelInterface::class)])
            ->setPublic(true)
        ;

        $container
            ->setAlias('phprunner.logger', 'logger')
            ->setPublic(true);

//        $container
//            ->register('workerman.task_handler', TaskHandler::class)
//            ->setPublic(true)
//            ->setArguments([
//                new Reference('workerman.task_locator'),
//                new Reference(EventDispatcherInterface::class),
//            ])
//        ;
//
//        $container
//            ->register('workerman.process_handler', ProcessHandler::class)
//            ->setPublic(true)
//            ->setArguments([
//                new Reference('workerman.process_locator'),
//                new Reference(EventDispatcherInterface::class),
//            ])
//        ;
    }

    private function referenceMap(array $taggedServices): array
    {
        $result = [];
        foreach ($taggedServices as $id => $tags) {
            $result[$id] = new Reference($id);
        }
        return $result;
    }
}
