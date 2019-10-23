<?php
declare(strict_types=1);
namespace Tests\Link;

use PHPUnit\Framework\TestCase;
use df\link\http\Client;
use df\link\http\IResponse;

class HttpClientTest extends TestCase
{
    public function testGet()
    {
        $client = new Client();
        $response = $client->get('https://raw.githubusercontent.com/decodelabs/df-r7-base/master/README.md');

        $this->assertInstanceOf(IResponse::class, $response);
        $this->assertTrue($response->isOk());
        $this->assertSame(200, $response->getHeaders()->getStatusCode());
        $this->assertSame('text/plain; charset=utf-8', $response->getContentType());

        $file = $response->getContentFileStream();
        $this->assertSame('# R7 Base', $file->readLine());
        $file->close();
    }

    public function testGetFile()
    {
        $client = new Client();

        $response = $client->getFile(
            'https://raw.githubusercontent.com/decodelabs/df-r7-base/master/README.md',
            __DIR__
        );

        $this->assertInstanceOf(IResponse::class, $response);
        $this->assertTrue($response->isOk());
        $this->assertSame(200, $response->getHeaders()->getStatusCode());
        $this->assertSame('text/plain; charset=utf-8', $response->getContentType());

        $file = $response->getContentFileStream();
        $this->assertSame('# R7 Base', $file->readLine());
        $file->delete();
    }
}
