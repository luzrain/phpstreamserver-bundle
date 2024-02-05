<?php

declare(strict_types=1);

include __DIR__ . '/../../vendor/autoload.php';

//\startServer();
//\register_shutdown_function(stopServer(...));

function startServer(): void
{
    $descriptorspec = [['pipe', 'r'], ['pipe', 'w']];
    $process = \proc_open(\getServerStartCommandLine('start -d'), $descriptorspec, $pipes);
    $return = \proc_close($process);
    !$return ?: exit("Server start failed\n");
    \usleep(500);
}

function stopServer(): void
{
    \exec(\getServerStartCommandLine('stop'));
}

function getServerStartCommandLine(string $command): string
{
    return \sprintf('exec %s %s/index.php %s', PHP_BINARY, __DIR__, $command);
}
