<?php

declare(strict_types=1);

namespace Luzrain\PHPStreamServerBundle\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Stream;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RequestTest extends KernelTestCase
{
    public function testRequestNotFound(): void
    {
        // Act
        $client = new Client(['http_errors' => false]);
        $response = $client->request('GET', 'http://127.0.0.1:8888/qqqqqqqq');

        // Assert
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testHeaders(): void
    {
        // Act
        $response = $this->createResponse('GET', [
            'headers' => [
                'test-header-1' => '9hnwk8xuxzt8qdc4wcsrr26uqqsuz8',
                'test-header-2' => '888888889999999',
            ],
        ]);

        // Assert
        $this->assertSame('9hnwk8xuxzt8qdc4wcsrr26uqqsuz8', $response['headers']['test-header-1'][0]);
        $this->assertSame('888888889999999', $response['headers']['test-header-2'][0]);
    }

    public function testGetRequest(): void
    {
        // Act
        $response = $this->createResponse('GET', [
            'query' => [
                'test-query-1' => '3kqz7kx610uewmcwyg44z',
                'test-query-2' => '123456',
            ],
        ]);

        // Assert
        $this->assertSame('3kqz7kx610uewmcwyg44z', $response['get']['test-query-1']);
        $this->assertSame('123456', $response['get']['test-query-2']);
    }

    public function testPostRequest(): void
    {
        // Act
        $response = $this->createResponse('POST', [
            'form_params' => [
                'test-post-1' => '88lc5paair2x',
                'test-post-2' => '222333444',
            ],
        ]);

        // Assert
        $this->assertSame('88lc5paair2x', $response['post']['test-post-1']);
        $this->assertSame('222333444', $response['post']['test-post-2']);
    }

    public function testPostJsonRequest(): void
    {
        // Act
        $response = $this->createResponse('POST', [
            'json' => [
                'test-1' => 'c865admkpp39',
                'test-2' => null,
                'test-3' => ['t1' => 'test1', 't2' => 123],
            ],
        ]);

        // Assert
        $this->assertSame('c865admkpp39', $response['post']['test-1']);
        $this->assertSame(null, $response['post']['test-2']);
        $this->assertSame(['t1' => 'test1', 't2' => 123], $response['post']['test-3']);
    }

    public function testCookies(): void
    {
        // Act
        $response = $this->createResponse('POST', [
            'cookies' => CookieJar::fromArray(domain: '127.0.0.1', cookies: [
                'test-cookie-1' => '94bt5trqjfqe6seo0',
                'test-cookie-2' => 'test8888',
            ]),
        ]);

        // Assert
        $this->assertSame('94bt5trqjfqe6seo0', $response['cookies']['test-cookie-1']);
        $this->assertSame('test8888', $response['cookies']['test-cookie-2']);
    }

    public function testMultipartRequest(): void
    {
        // Arrange
        $bigFileResource = \fopen('php://temp', 'rw');
        for ($i = 0; $i < 50; $i++) {
            \fwrite($bigFileResource, \str_repeat('0', 100000));
        }
        \rewind($bigFileResource);

        // Act
        $response = $this->createResponse('POST', [
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=OEZCxUAIiopEcaUw',
            ],
            'body' => new MultipartStream(boundary: 'OEZCxUAIiopEcaUw', elements: [
                [
                    'name' => 'test-1',
                    'contents' => 'test-1-data',
                ],
                [
                    'name' => 'test-2',
                    'contents' => 'test-2-data',
                ],
                [
                    'name' => 'file_one[]',
                    'filename' => 'test1.txt',
                    'contents' => "b8owxkeuhjeq3kqz7kx610uewmcwygap content\nooooommm mmezxssdfdsfd123123123123",
                ],
                [
                    'name' => 'file_one[]',
                    'filename' => 'test2.txt',
                    'contents' => '11111111111111111111111122222222222233333333339',
                ],
                [
                    'name' => 'file_three',
                    'filename' => 'test3.txt',
                    'contents' => 'test content for file three',
                ],
                [
                    'name' => 'image',
                    'filename' => 'dot.png',
                    'contents' => \base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', true),
                ],
                [
                    'name' => 'big_file',
                    'filename' => 't.bin',
                    'contents' => new Stream($bigFileResource),
                ],
            ]),
        ]);

        // Assert request
        $this->assertCount(2, $response['post']);
        $this->assertSame('test-1-data', $response['post']['test-1']);
        $this->assertSame('test-2-data', $response['post']['test-2']);

        // Assert files
        $file = $response['files']['file_one'][0];
        $this->assertSame('test1.txt', $file['filename']);
        $this->assertSame('txt', $file['extension']);
        $this->assertSame(75, $file['size']);
        $this->assertSame('781eaba2e9a92ddf42748bd8f56a9990459ea413', $file['sha1']);

        $file = $response['files']['file_one'][1];
        $this->assertSame('test2.txt', $file['filename']);
        $this->assertSame('txt', $file['extension']);
        $this->assertSame(47, $file['size']);
        $this->assertSame('f69850b7b6dddf24c14581956f5b6aa3ae9cd54e', $file['sha1']);

        $file = $response['files']['file_three'];
        $this->assertSame('test3.txt', $file['filename']);
        $this->assertSame('txt', $file['extension']);
        $this->assertSame(27, $file['size']);
        $this->assertSame('4c129254b51981cba03e4c8aac82bb329880971a', $file['sha1']);

        $file = $response['files']['image'];
        $this->assertSame('dot.png', $file['filename']);
        $this->assertSame('png', $file['extension']);
        $this->assertSame(70, $file['size']);
        $this->assertSame('4a5eb7171b58e08a6881721e3b43d5a44419a2be', $file['sha1']);

        $file = $response['files']['big_file'];
        $this->assertSame('t.bin', $file['filename']);
        $this->assertSame('bin', $file['extension']);
        $this->assertSame(5000000, $file['size']);
        $this->assertSame('1310fa4a837135d0a5d13388a21e49474eea00ac', $file['sha1']);
    }

    public function testRawRequest(): void
    {
        $response = $this->createResponse('POST', [
            'body' => '88lc5paair2xwnidlz9r6k0rpggkmbhb2oqr0go0cxc',
        ]);

        $this->assertSame('88lc5paair2xwnidlz9r6k0rpggkmbhb2oqr0go0cxc', $response['raw_request']);
    }

    private function createResponse(string $method, array $options = []): array
    {
        $client = new Client(['http_errors' => false]);
        $response = $client->request($method, 'http://127.0.0.1:8888/request_test', $options);

        return \json_decode((string) $response->getBody(), true);
    }
}
