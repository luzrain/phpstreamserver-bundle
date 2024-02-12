<?php

declare(strict_types=1);

include __DIR__ . '/../../vendor/autoload.php';

\startServer();
\register_shutdown_function(stopServer(...));
\register_shutdown_function(static function () {
    if (\is_dir($dir = \realpath(__DIR__ . '/../../var/cache/test'))) {
        \exec("rm -rf $dir");
    }
});

function startServer(): void
{
    $process = \proc_open(\getServerStartCommandLine('start -d'), [], $pipes);
    !\proc_close($process) ?: exit("Server start failed\n");
    \usleep(10000);
}

function stopServer(): void
{
    $process = \proc_open(\getServerStartCommandLine('stop'), [], $pipes);
    \proc_close($process);
}

function getServerStartCommandLine(string $command): string
{
    return \sprintf('exec %s %s/index.php %s', PHP_BINARY, __DIR__, $command);
}
