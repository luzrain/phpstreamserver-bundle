<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle;

use Luzrain\PhpRunnerBundle\DependencyInjection\CompilerPass;
use Luzrain\PhpRunnerBundle\DependencyInjection\PhpRunnerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class PhpRunnerBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CompilerPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new PhpRunnerExtension();
    }
}
