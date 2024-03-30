<?php

declare(strict_types=1);

namespace Luzrain\PHPStreamServerBundle\Http;

use Symfony\Contracts\Service\ResetInterface;

final class HeadersService implements ResetInterface
{
    private static \WeakReference|null $instance = null;

    public function __construct()
    {
        include_once __DIR__ . '/headers_functions.php';
        self::$instance = \WeakReference::create($this);
    }

    public static function getInstance(): self
    {
        return self::$instance?->get() ?? throw new \LogicException('Service not yet instantiated');
    }

    public function reset()
    {
        $this->headerRemove();
    }

    public function headersSent(string &$filename = null, int &$line = null): bool
    {
        dump('headersSent');

        return false;
    }

    public function header(string $header, bool $replace = true, int $responseCode = 0): void
    {
        dump('header');
    }

    public function headersSend(int $statusCode): void
    {
        dump('headersSend');
    }

    public function headerRemove(string|null $name = null): void
    {
        dump('headerRemove');
    }
}
