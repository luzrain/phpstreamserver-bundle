<?php

declare(strict_types=1);

use Luzrain\PhpRunnerBundle\Test\App\Kernel;

require_once \dirname(__DIR__, 2) . '/vendor/autoload_runtime.php';

$_SERVER['APP_RUNTIME'] = 'Luzrain\PhpRunnerBundle\Runtime';
$_SERVER['SHELL_VERBOSITY'] = -1;

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
