<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http;

use DecodeLabs\Exceptional;

use df\core;

class Cookie implements ICookie
{
    use core\TStringProvider;

    protected $_name;
    protected $_value;
    protected $_expiryDate;
    protected $_domain;
    protected $_path;
    protected $_isSecure = false;
    protected $_isHttpOnly = false;

    public static function fromString(string $string)
    {
        $parts = explode(';', $string);
        $main = explode('=', trim((string)array_shift($parts)), 2);
        $output = new self(array_shift($main), array_shift($main));
        $hasMaxAge = false;

        foreach ($parts as $part) {
            $set = explode('=', trim((string)$part), 2);
            $key = strtolower((string)array_shift($set));
            $value = trim((string)array_shift($set));

            switch ($key) {
                case 'max-age':
                    $output->setMaxAge($value);
                    $hasMaxAge = true;
                    break;

                case 'expires':
                    if (!$hasMaxAge) {
                        $output->setExpiryDate($value);
                    }
                    break;

                case 'domain':
                    $output->setDomain($value);
                    break;

                case 'path':
                    $output->setPath($value);
                    break;

                case 'secure':
                    $output->isSecure(true);
                    break;

                case 'httponly':
                    $output->isHttpOnly(true);
                    break;
            }
        }

        return $output;
    }

    public function __construct($name, $value, $expiry = null, $httpOnly = null, $secure = null)
    {
        $this->setName($name);
        $this->setValue($value);

        if ($expiry !== null) {
            $this->setExpiryDate($expiry);
        }

        if ($httpOnly !== null) {
            $this->isHttpOnly((bool)$httpOnly);
        }

        if ($secure !== null) {
            $this->isSecure((bool)$secure);
        }
    }

    public function setName($name)
    {
        $name = (string)$name;

        if (empty($name) && !is_numeric($name)) {
            throw Exceptional::InvalidArgument(
                'Empty cookie name'
            );
        }

        if (preg_match('/[\x00-\x20\x22\x28-\x29\x2c\x2f\x3a-\x40\x5b-\x5d\x7b\x7d\x7f]/', $name)) {
            throw Exceptional::InvalidArgument(
                'Cookie name contains control character or space'
            );
        }

        $this->_name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function matchesName($name)
    {
        if ($name === null) {
            return true;
        }

        return $this->_name === $name;
    }


    public function setValue($value)
    {
        $this->_value = (string)$value;
        return $this;
    }

    public function getValue()
    {
        return $this->_value;
    }


    public function setMaxAge($age = null)
    {
        if (!empty($age)) {
            $this->setExpiryDate(core\time\Date::factory('now')->add($age));
        } else {
            $this->setExpiryDate(null);
        }

        return $this;
    }

    public function getMaxAge()
    {
        if (!$this->_expiryDate) {
            return null;
        }

        return $this->_expiryDate->toTimestamp() - time();
    }


    public function setExpiryDate($date = null)
    {
        $this->_expiryDate = core\time\Date::normalize($date);
        return $this;
    }

    public function getExpiryDate()
    {
        return $this->_expiryDate;
    }

    public function isExpired()
    {
        if (!$this->_expiryDate) {
            return false;
        }

        return $this->_expiryDate->isPast();
    }


    public function setDomain($domain)
    {
        $this->_domain = $domain;
        return $this;
    }

    public function getDomain()
    {
        return $this->_domain;
    }

    public function matchesDomain($domain)
    {
        if ($domain === null) {
            return true;
        }

        $current = ltrim((string)$this->_domain, '.');

        if (!$current || !strcasecmp($domain, $current)) {
            return true;
        }

        if (filter_var($domain, \FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool)preg_match(
            '/\.' . preg_quote($current) . '$/i',
            $domain
        );
    }

    public function setPath($path)
    {
        $this->_path = $path;
        return $this;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function matchesPath($path)
    {
        if ($path === null) {
            return true;
        }

        if (!strlen((string)$this->_path)) {
            return true;
        }

        $path = '/' . ltrim((string)$path, '/');
        $test = '/' . ltrim((string)$this->_path, '/');

        return 0 === stripos($path, $test);
    }


    public function isSecure(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isSecure = $flag;
            return $this;
        }

        return $this->_isSecure;
    }

    public function isHttpOnly(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isHttpOnly = $flag;
            return $this;
        }

        return $this->_isHttpOnly;
    }

    // String
    public function toString(): string
    {
        $output = $this->_name . '=' . urlencode($this->_value);

        if ($this->_expiryDate) {
            $output .= '; Expires=' . $this->_expiryDate->toTimezone('GMT')->format(core\time\Date::COOKIE);
        }

        if ($this->_domain !== null) {
            $output .= '; Domain=' . $this->_domain;
        }

        if ($this->_path !== null) {
            $output .= '; Path=' . $this->_path;
        }

        if ($this->_isSecure) {
            $output .= '; Secure';
        }

        if ($this->_isHttpOnly) {
            $output .= '; HttpOnly';
        }

        return $output;
    }

    public function toInvalidateString()
    {
        $output = $this->_name . '=deleted';
        $output .= '; Expires=' . core\time\Date::factory('-10 years', 'GMT')->format(core\time\Date::COOKIE);

        if ($this->_domain !== null) {
            $output .= '; Domain=' . $this->_domain;
        }

        if ($this->_path !== null) {
            $output .= '; Path=' . $this->_path;
        }

        if ($this->_isSecure) {
            $output .= '; Secure';
        }

        if ($this->_isHttpOnly) {
            $output .= '; HttpOnly';
        }

        return $output;
    }
}
