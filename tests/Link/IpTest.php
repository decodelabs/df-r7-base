<?php
declare(strict_types=1);
namespace Tests\Link;

use PHPUnit\Framework\TestCase;
use df\link\Ip;

class IpTest extends TestCase
{
    public function testFactory()
    {
        $this->assertInstanceOf(Ip::class, $ip = Ip::factory('192.168.1.1'));
        $this->assertSame('192.168.1.1', (string)$ip);

        $this->assertInstanceOf(Ip::class, $ip = Ip::factory('2a00:23c6:6407:9e00:49cf:d932:aee9:4a18'));
        $this->assertSame('2a00:23c6:6407:9e00:49cf:d932:aee9:4a18', (string)$ip);
    }

    /**
     * @dataProvider ipProvider
     */
    public function testV4(string $ip, bool $v4, bool $v6, bool $loopback)
    {
        $this->assertSame($v4, Ip::factory($ip)->isV4());
    }

    /**
     * @dataProvider ipProvider
     */
    public function testV6(string $ip, bool $v4, bool $v6, bool $loopback)
    {
        $this->assertSame($v6, Ip::factory($ip)->isV6());
    }

    /**
     * @dataProvider ipProvider
     */
    public function testHybrid(string $ip, bool $v4, bool $v6, bool $loopback)
    {
        $this->assertSame($v4 && $v6, Ip::factory($ip)->isHybrid());
    }

    /**
     * @dataProvider ipProvider
     */
    public function testLoopback(string $ip, bool $v4, bool $v6, bool $loopback)
    {
        $this->assertSame($loopback, Ip::factory($ip)->isLoopback());
    }


    public function ipProvider()
    {
        return [
            'v4' => ['86.138.40.94', true, false, false],
            'v4Loopback' => ['127.0.0.1', true, false, true],
            'hybrid' => ['0:0:0:0:0:ffff:86.138.40.94', true, true, false],
            'hybridLoopback' => ['0:0:0:0:0:ffff:127.0.0.1', true, true, true],
            'v6' => ['2a00:23c6:6407:9e00:49cf:d932:aee9:4a18', false, true, false],
            'v6Loopback' => ['0:0:0:0:0:0:0:1', false, true, true]
        ];
    }
}
