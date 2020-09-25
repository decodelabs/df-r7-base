<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link;

use df\link\Ip;
use df\core\IStringProvider;
use df\core\TStringProvider;
use df\flex\Text;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class IpRange implements IStringProvider, Dumpable
{
    use TStringProvider;

    protected $isV4 = false;
    protected $start = null;
    protected $end = null;
    protected $netmask = null;
    protected $isValid = false;

    public static function factory($range): IpRange
    {
        if ($range instanceof self) {
            return $range;
        }

        return new self($range);
    }

    public function __construct(string $range)
    {
        if (false !== strpos($range, ':')) {
            $this->_parseV6($range);
        } else {
            $this->_parseV4($range);
        }
    }

    protected function _parseV4(string $range): void
    {
        $this->isV4 = true;

        if (false !== strpos($range, '/')) {
            // CIDR
            list($range, $netmask) = explode('/', $range, 2);
            $parts = explode('.', $range);

            while (count($parts) < 4) {
                $parts[] = '0';
            }

            list($a, $b, $c, $d) = $parts;
            $range = ip2long(sprintf('%u.%u.%u.%u', $a, $b, $c, $d));

            if (!$range) {
                return;
            }

            $this->start = $range;

            if (false !== strpos($netmask, '.')) {
                // 255.255.0.0
                $this->netmask = ip2long(str_replace('*', '0', $netmask));
            } else {
                // /24
                $this->netmask = -pow(2, (32 - (int)$netmask));
            }
        } else {
            if (false !== strpos($range, '*')) {
                // Wildcards
                $range = str_replace('*', '0', $range).'-'.str_replace('*', '255', $range);
            }

            if (false !== strpos($range, '-')) {
                // Simple range
                $parts = explode('-', $range, 2);
                $this->start = ip2long(trim((string)array_shift($parts)));
                $this->end = ip2long(trim((string)array_shift($parts)));
            } else {
                // Single ip match
                $this->start = $this->end = (int)Ip::factory($range)->getV4Decimal();
            }
        }

        $this->isValid = true;
    }

    protected function _parseV6(string $range): void
    {
        $this->isV4 = false;

        if (false !== strpos($range, '/')) {
            // CIDR
            list($range, $netmask) = explode('/', $range, 2);
            $ip = new Ip($range);
            $range = $ip->getV6Decimal();

            if (is_numeric($netmask)) {
                $netmask = (int)$netmask;

                if ($netmask >= 0 && $netmask <= 128) {
                    if ($netmask == 0) {
                        $range = 0;
                    } else {
                        $range = Text::baseConvert($range, 10, 2, 128);
                        $range = str_pad(substr($range, 0, $netmask), 128, '0', STR_PAD_RIGHT);
                        $range = Text::baseConvert($range, 2, 10);
                    }
                }
            }

            $this->start = Text::baseConvert($range, 10, 16, 32);
            $this->end = Text::baseConvert($range, 10, 2, 128);
            $this->end = str_pad(substr($this->end, 0, (int)$netmask), 128, '1', STR_PAD_RIGHT);
            $this->end = Text::baseConvert($this->end, 2, 16, 32);
        } else {
            if (false !== strpos($range, '*')) {
                // Wildcards
                $range = str_replace('*', '0', $range).'-'.str_replace('*', 'ffff', $range);
            }

            if (false !== strpos($range, '-')) {
                // Simple range
                $parts = explode('-', $range, 2);
                $start = ip2long(trim((string)array_shift($parts)));
                $end = ip2long(trim((string)array_shift($parts)));

                if ($start < 0) {
                    $start += pow(2, 32);
                }

                if ($end < 0) {
                    $end += pow(2, 32);
                }

                $this->start = sprintf('%08x', $start);
                $this->end = sprintf('%08x', $end);
            } else {
                // Single ip match
                $this->start = $this->end = Ip::factory($range)->getV4Hex();
            }
        }

        $this->isValid = true;
    }

    public function check($ip): bool
    {
        if (!$this->isValid) {
            return false;
        }

        $ip = Ip::factory($ip);

        if ($this->isV4) {
            if (!$ip->isV4()) {
                return false;
            }

            $value = (int)$ip->getV4Decimal();

            if ($this->end !== null) {
                // range
                return $this->start <= $value && $value <= $this->end;
            } else {
                // netmask
                return ($value & $this->netmask)
                    == ($this->start & $this->netmask);
            }
        } else {
            // range
            $hex = $ip->getV6Hex();
            return $this->start <= $hex && $hex <= $this->end;
        }
    }

    public function toString(): string
    {
        if ($this->isV4) {
            return $this->_toV4String();
        } else {
            return $this->_toV6String();
        }
    }

    protected function _toV4String(): string
    {
        if ($this->end !== null) {
            // Hex
            $start = long2ip($this->start);
            $end = long2ip($this->end);

            if ($start == $end) {
                return $start;
            }

            return $start.'-'.$end;
        } else {
            // Mask
            $start = long2ip($this->start);
            $netmask = 32 - (log($this->netmask * -1) / log(2));

            if (is_nan($netmask)) {
                $netmask = long2ip($this->netmask);
            }

            return $start.'/'.$netmask;
        }
    }

    protected function _toV6String(): string
    {
        Glitch::incomplete();
    }

    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        if ($this->isV4) {
            yield 'text' => $this->_toV4String();
        } else {
            yield 'properties' => [
                '*v6' => true,
                '*start' => $this->start,
                '*end' => $this->end,
                '*netmask' => $this->netmask
            ];
        }
    }
}
