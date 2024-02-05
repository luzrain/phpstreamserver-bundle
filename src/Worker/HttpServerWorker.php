<?php

declare(strict_types=1);

namespace Luzrain\PhpRunnerBundle\Worker;

use Luzrain\PhpRunner\Server\Connection\ConnectionInterface;
use Luzrain\PhpRunner\Server\Protocols\Http;
use Luzrain\PhpRunner\Server\Server;
use Luzrain\PhpRunner\WorkerProcess;
use Luzrain\PhpRunnerBundle\Event\HttpServerStartEvent;
use Luzrain\PhpRunnerBundle\KernelFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class HttpServerWorker extends WorkerProcess
{
    private string $listen;
    private bool $tls;

    public function __construct(
        private readonly KernelFactory $kernelFactory,
        string $listen,
        private readonly string|null $localCert,
        private readonly string|null $localPk,
        string $name,
        int $count,
        string|null $user,
        string|null $group,
    ) {
        if (!\str_starts_with($listen, 'http://') && !\str_starts_with($listen, 'https://')) {
            throw new \InvalidArgumentException('HttpServerWorker only supports http:// and https:// listen');
        }

        $this->tls = \str_starts_with($listen, 'https://');
        $this->listen = \str_replace(['http://', 'https://'], 'tcp://', $listen);

        parent::__construct(
            name: $name,
            count: $count,
            user: $user,
            group: $group,
            onStart: $this->onStart(...),
        );
    }

    private function onStart(): void
    {
        $kernel = $this->kernelFactory->createKernel();
        $kernel->boot();
        $kernel->getContainer()->set('phprunner.worker', $this);

        /** @var \Closure $httpHandler */
        $httpHandler = $kernel->getContainer()->get('phprunner.http_request_handler');

        /** @var LoggerInterface $logger */
        $logger = $kernel->getContainer()->get('phprunner.logger');

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $kernel->getContainer()->get('event_dispatcher');

        /**
         * @psalm-suppress UndefinedClass
         * @psalm-suppress UndefinedInterfaceMethod
         */
        if ($logger instanceof \Monolog\Logger) {
            $logger = $logger->withName('phprunner');
        }

        $errorHandler = ErrorHandler::register(null, false);
        $errorHandlerClosure = static function (\Throwable $e) use ($errorHandler): void {
            $errorHandler->setExceptionHandler(static function (\Throwable $e): void {});
            /** @psalm-suppress InternalMethod */
            $errorHandler->handleException($e);
        };

        $this->setLogger($logger);
        $this->setErrorHandler($errorHandlerClosure);

        $this->startServer(new Server(
            listen: $this->listen,
            protocol: new Http(),
            tls: $this->tls,
            tlsCertificate: $this->localCert ?? '',
            tlsCertificateKey: $this->localPk ?? '',
            onMessage: function (ConnectionInterface $connection, ServerRequestInterface $data) use ($httpHandler) {
                $connection->send($httpHandler($data));
            },
        ));

        $eventDispatcher->dispatch(new HttpServerStartEvent($this));
    }
}
