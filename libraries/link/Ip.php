<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link;

use df\core\IStringProvider;
use df\link\IpRange;
use df\flex\Text;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Ip implements IStringProvider, Inspectable
{
    protected $ip;
    protected $isV4 = false;
    protected $isV6 = false;

    public static function factory($ip): Ip
    {
        if ($ip instanceof self) {
            return $ip;
        }

        return new self($ip);
    }

    public static function normalize($ip): ?Ip
    {
        if (empty($ip)) {
            return null;
        }

        return self::factory($ip);
    }

    public function __construct(string $ip)
    {
        if ($ip == 'localhost') {
            $ip = '127.0.0.1';
        }

        $in = $ip;
        $this->isV4 = $hasV4 = strpos($ip, '.') > 0;
        $this->isV6 = $hasV6 = strpos($ip, ':') !== false;

        if (!$hasV4 && !$hasV6) {
            throw Glitch::EInvalidArgument('Could not detect IPv4 or IPv6 signature - '.$ip);
        }

        if ($hasV4 && $hasV6) {
            // IPv6 with IPv4 compat - strip the compat
            $ip = substr($ip, strrpos($ip, ':') + 1);
            $hasV6 = false;
        }

        if ($hasV4) {
            $ip = array_pad(explode('.', $ip), 4, 0);

            if (count($ip) > 4) {
                throw Glitch::EInvalidArgument($in.' is not a valid IPv4 address');
            }

            for ($i = 0; $i < 4; $i++) {
                if ($ip[$i] > 255) {
                    throw Glitch::EInvalidArgument($in.' is not a valid IPv4 address');
                }
            }

            $part7 = base_convert((string)(($ip[0] * 256) + $ip[1]), 10, 16);
            $part8 = base_convert((string)(($ip[2] * 256) + $ip[3]), 10, 16);

            $ip = '::ffff:'.$part7.':'.$part8;
        }

        $ip = strtolower($ip);

        if (false !== strpos($ip, '::')) {
            $ip = str_replace('::', str_repeat(':0', 8 - substr_count($ip, ':')).':', $ip);
        }

        if (0 === strpos($ip, ':')) {
            $ip = '0'.$ip;
        }

        $this->ip = $ip;
    }


    // Ranges
    public function isInRange($range): bool
    {
        return IpRange::factory($range)->check($this);
    }

    public function isV4(): bool
    {
        return $this->isV4;
    }

    public function isStandardV4(): bool
    {
        return $this->isV4 && !$this->isV6;
    }

    public function isV6(): bool
    {
        return $this->isV6;
    }

    public function isStandardV6(): bool
    {
        return $this->isV6 && !$this->isV4;
    }

    public function isHybrid(): bool
    {
        return $this->isV4 && $this->isV6;
    }

    public function convertToV6(): Ip
    {
        $this->isV6 = true;
        return $this;
    }


    // Strings
    public function __toString(): string
    {
        try {
            return $this->toString();
        } catch (\Throwable $e) {
            return '0.0.0.0';
        }
    }

    public function toString(): string
    {
        if ($this->isStandardV4()) {
            return $this->getV4String();
        } else {
            return $this->getCompressedV6String();
        }
    }

    public function getV6String(): string
    {
        if ($this->isV4) {
            return '0:0:0:0:0:ffff:'.$this->getV4String();
        }

        return $this->ip;
    }

    public function getCompressedV6String(): string
    {
        if ($this->isV4) {
            return '::ffff:'.$this->getV4String();
        }

        $ip = ':'.$this->ip.':';
        preg_match_all('/(:0)+/', $ip, $matches);

        if (isset($matches[0]) && !empty($matches[0])) {
            $match = '';

            foreach ($matches[0] as $zero) {
                if (strlen($zero) > strlen($match)) {
                    $match = $zero;
                }
            }

            $ip = (string)preg_replace('/'.$match.'/', ':', $ip, 1);
        }

        $ip = (string)preg_replace('/((^:)|(:$))/', '', $ip);
        $ip = (string)preg_replace('/((^:)|(:$))/', '::', $ip);

        return $ip;
    }

    public function getV4String(): string
    {
        if (!$this->isV4) {
            throw Glitch::ERuntime('Ip is not in V4 range');
        }

        $pos = strrpos($this->ip, ':');
        $part1 = (int)base_convert(substr($this->ip, 15, $pos - 15), 16, 10);
        $part2 = (int)base_convert(substr($this->ip, $pos + 1), 16, 10);

        $b = ($part1 % 256);
        $a = ($part1 - $b) / 256;
        $d = ($part2 % 256);
        $c = ($part2 - $d) / 256;

        return $a.'.'.$b.'.'.$c.'.'.$d;
    }


    // Base conversion
    public function getV6Decimal(): string
    {
        return Text::baseConvert($this->getV6Hex(), 16, 10);
    }

    public function getV4Decimal(): string
    {
        return Text::baseConvert($this->getV4Hex(), 16, 10);
    }


    public function getV6Hex(): string
    {
        $parts = explode(':', $this->ip);
        $output = '';

        foreach ($parts as $part) {
            $output .= str_pad($part, 4, '0', STR_PAD_LEFT);
        }

        return $output;
    }

    public function getV4Hex(): string
    {
        if (!$this->isV4()) {
            throw Glitch::ERuntime('Ip is not in V4 range');
        }

        $parts = array_slice(explode(':', $this->ip), -2);
        $output = '';

        foreach ($parts as $part) {
            $output .= str_pad($part, 4, '0', STR_PAD_LEFT);
        }

        return $output;
    }


    // Loopback
    public static function getV4Loopback(): Ip
    {
        return new self('127.0.0.1');
    }

    public static function getV6Loopback(): Ip
    {
        return new self('::1');
    }

    public function isLoopback(): bool
    {
        return $this->isV4Loopback()
            || $this->isV6Loopback();
    }

    public function isV6Loopback(): bool
    {
        return $this->ip == '0:0:0:0:0:0:0:1';
    }

    public function isV4Loopback(): bool
    {
        return $this->ip == '0:0:0:0:0:ffff:7f00:1';
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->__toString());
    }
}
