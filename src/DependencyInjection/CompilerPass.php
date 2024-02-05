<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        //$tasks = array_map(fn(array $a) => $a[0], $container->findTaggedServiceIds('phprunner.task'));
        //$processes = array_map(fn(array $a) => $a[0], $container->findTaggedServiceIds('phprunner.process'));

        //        $container
        //            ->getDefinition('phprunner.config_loader')
        //            ->addMethodCall('setProcessConfig', [$processes])
        //            ->addMethodCall('setSchedulerConfig', [$tasks])
        //        ;

        //        $container
        //            ->register('phprunner.task_locator', ServiceLocator::class)
        //            ->addTag('container.service_locator')
        //            ->setArguments([$this->referenceMap($tasks)])
        //        ;
        //
        //        $container
        //            ->register('phprunner.process_locator', ServiceLocator::class)
        //            ->addTag('container.service_locator')
        //            ->setArguments([$this->referenceMap($processes)])
        //        ;

        //        $container
        //            ->register('phprunner.task_handler', TaskHandler::class)
        //            ->setPublic(true)
        //            ->setArguments([
        //                new Reference('phprunner.task_locator'),
        //                new Reference(EventDispatcherInterface::class),
        //            ])
        //        ;
        //
        //        $container
        //            ->register('phprunner.process_handler', ProcessHandler::class)
        //            ->setPublic(true)
        //            ->setArguments([
        //                new Reference('phprunner.process_locator'),
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
