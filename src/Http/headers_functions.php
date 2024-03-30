<?php

declare(strict_types=1);

/**
 * Defining dummy headers_send function in global namespace just to pass HttpFoundation SAPI check
 */
namespace
{
    function headers_send(int $status_code): void {
        throw new \LogicException('Use \Symfony\Component\HttpFoundation\headers_send() function');
    }
}

/**
 * Defining header* functions in the HttpFoundation namespace forces those functions to be used instead
 * of the global versions, which don't work in the CLI.
 */
namespace Symfony\Component\HttpFoundation
{
    use Luzrain\PHPStreamServerBundle\Http\HeadersService;

    function headers_sent(string &$filename = null, int &$line = null): bool {
        return HeadersService::getInstance()->headersSent($filename, $line);
    }

    function header(string $header, bool $replace = true, int $response_code = 0): void {
        HeadersService::getInstance()->header($header, $replace, $response_code);
    }

    function headers_send(int $status_code): void {
        HeadersService::getInstance()->headersSend($status_code);
    }

    function header_remove(string|null $name = null): void {
        HeadersService::getInstance()->headerRemove($name);
    }
}
