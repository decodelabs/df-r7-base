<?php
declare(strict_types=1);
namespace df\tests\link;

use PHPUnit\Framework\TestCase;
use df\link\Ip;
use df\link\IpRange;

class IpRangeTest extends TestCase
{
    public function testFactory()
    {
        $this->assertInstanceOf(IpRange::class, $range = IpRange::factory('192.168.1.1-192.168.1.255'));
        $this->assertSame('192.168.1.1-192.168.1.255', (string)$range);
    }

    /**
     * @dataProvider rangeProvider
     */
    public function testRange(string $range, string $ip, bool $expect)
    {
        $range = IpRange::factory($range);
        $this->assertSame($expect, $range->check($ip));
    }

    public function rangeProvider()
    {
        return [
            ['10.0.0.0/24', '10.0.0.1', true],
            ['10.0.0.0/24', '9.0.0.1', false],
            ['10.0.0.0-10.0.0.255', '10.0.0.1', true],
            ['10.0.0.0-10.0.0.255', '9.0.0.1', false],
        ];
    }
}
