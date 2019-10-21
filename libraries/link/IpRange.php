<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link;

use df;
use df\core;
use df\link;
use df\flex;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class IpRange implements IIpRange, Inspectable
{
    use core\TStringProvider;

    protected $_isV4 = false;
    protected $_start = null;
    protected $_end = null;
    protected $_netmask = null;
    protected $_isValid = false;

    public static function factory($range)
    {
        if ($range instanceof self) {
            return $range;
        }

        return new self($range);
    }

    public function __construct($range)
    {
        if (false !== strpos($range, ':')) {
            $this->_parseV6($range);
        } else {
            $this->_parseV4($range);
        }
    }

    protected function _parseV4($range)
    {
        $this->_isV4 = true;

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
                return false;
            }

            $this->_start = $range;

            if (false !== strpos($netmask, '.')) {
                // 255.255.0.0
                $this->_netmask = ip2long(str_replace('*', '0', $netmask));
            } else {
                // /24
                $this->_netmask = -pow(2, (32 - $netmask));
            }
        } else {
            if (false !== strpos($range, '*')) {
                // Wildcards
                $range = str_replace('*', '0', $range).'-'.str_replace('*', '255', $range);
            }

            if (false !== strpos($range, '-')) {
                // Simple range
                $parts = explode('-', $range, 2);
                $this->_start = ip2long(trim(array_shift($parts)));
                $this->_end = ip2long(trim(array_shift($parts)));
            } else {
                // Single ip match
                $this->_start = $this->_end = (int)Ip::factory($range)->getV4Decimal();
            }
        }

        $this->_isValid = true;
    }

    protected function _parseV6($range)
    {
        $this->_isV4 = false;

        if (false !== strpos($range, '/')) {
            // CIDR
            list($range, $netmask) = explode('/', $range, 2);
            $ip = new Ip($range);
            $range = $ip->getV6Decimal();

            if (is_numeric($netmask) && $netmask >= 0 && $netmask <= 128) {
                if ($netmask == 0) {
                    $range = 0;
                } else {
                    $range = flex\Text::baseConvert($range, 10, 2, 128);
                    $range = str_pad(substr($range, 0, $netmask), 128, 0, STR_PAD_RIGHT);
                    $range = flex\Text::baseConvert($range, 2, 10);
                }
            }

            $this->_start = flex\Text::baseConvert($range, 10, 16, 32);
            $this->_end = flex\Text::baseConvert($range, 10, 2, 128);
            $this->_end = str_pad(substr($this->_end, 0, $netmask), 128, 1, STR_PAD_RIGHT);
            $this->_end = flex\Text::baseConvert($this->_end, 2, 16, 32);
        } else {
            if (false !== strpos($range, '*')) {
                // Wildcards
                $range = str_replace('*', '0', $range).'-'.str_replace('*', 'ffff', $range);
            }

            if (false !== strpos($range, '-')) {
                // Simple range
                $parts = explode('-', $range, 2);
                $start = ip2long(trim(array_shift($parts)));
                $end = ip2long(trim(array_shift($parts)));

                if ($start < 0) {
                    $start += pow(2, 32);
                }

                if ($end < 0) {
                    $end += pow(2, 32);
                }

                $this->_start = sprintf('%08x', $start);
                $this->_end = sprintf('%08x', $end);
            } else {
                // Single ip match
                $this->_start = $this->_end = Ip::factory($range)->getV4Hex();
            }
        }

        $this->_isValid = true;
    }

    public function check($ip)
    {
        if (!$this->_isValid) {
            return false;
        }

        $ip = Ip::factory($ip);

        if ($this->_isV4) {
            if (!$ip->isV4()) {
                return false;
            }

            $value = (int)$ip->getV4Decimal();

            if ($this->_end !== null) {
                // range
                return $this->_start <= $value && $value <= $this->_end;
            } else {
                // netmask
                return ($value & $this->_netmask)
                    == ($this->_start & $this->_netmask);
            }
        } else {
            // range
            $hex = $ip->getV6Hex();
            return $this->_start <= $hex && $hex <= $this->_end;
        }
    }

    public function toString(): string
    {
        if ($this->_isV4) {
            return $this->_toV4String();
        } else {
            return $this->_toV6String();
        }
    }

    protected function _toV4String()
    {
        if ($this->_end !== null) {
            // Hex
            $start = long2ip($this->_start);
            $end = long2ip($this->_end);

            if ($start == $end) {
                return $start;
            }

            return $start.'-'.$end;
        } else {
            // Mask
            $start = long2ip($this->_start);
            $netmask = 32 - (log($this->_netmask * -1) / log(2));

            if (is_nan($netmask)) {
                $netmask = long2ip($this->_netmask);
            }

            return $start.'/'.$netmask;
        }
    }

    protected function _toV6String()
    {
        Glitch::incomplete();
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        if ($this->_isV4) {
            $entity->setText($this->_toV4String());
        } else {
            $entity->setProperties([
                '*v6' => $inspector(true),
                '*start' => $inspector($this->_start),
                '*end' => $inspector($this->_end),
                '*netmask' => $inspector($this->_netmask),
            ]);
        }
    }
}
