<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow\mail;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;
use df\core;

class Address implements IAddress, Dumpable
{
    use core\TStringProvider;

    protected $_name;
    protected $_address;
    protected $_isValid = null;

    /**
     * @return ($address is null ? null : self)
     */
    public static function factory($address, $name = null): ?self
    {
        if ($address === null) {
            return null;
        } elseif ($address instanceof Address) {
            return $address;
        } elseif ($name !== null) {
            return new self($address, $name);
        } elseif (is_string($address)) {
            return self::fromString($address);
        } else {
            throw Exceptional::InvalidArgument(
                'Invalid email address'
            );
        }
    }

    public static function fromString(string $string): Address
    {
        $parts = explode('<', $string, 2);

        $address = rtrim(trim((string)array_pop($parts)), '>');
        $name = trim((string)array_shift($parts), ' "\'');

        if (empty($name)) {
            $name = null;
        }

        return new self($address, $name);
    }

    public function __construct($address, $name = null)
    {
        $this->setAddress($address);
        $this->setName($name);
    }


    public function setAddress($address)
    {
        $address = strtolower((string)$address);
        $address = str_replace([' at ', ' dot '], ['@', '.'], $address);
        $address = filter_var($address, \FILTER_SANITIZE_EMAIL);

        $this->_address = $address;
        return $this;
    }

    public function getAddress()
    {
        return $this->_address;
    }

    public function getDomain()
    {
        $parts = explode('@', $this->_address, 2);
        $parts = explode('?', (string)array_pop($parts), 2);
        return array_shift($parts);
    }

    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->_name;
    }


    public function isValid(): bool
    {
        if ($this->_isValid === null) {
            $this->_isValid = (bool)filter_var($this->_address, \FILTER_VALIDATE_EMAIL);
        }

        return (bool)$this->_isValid;
    }

    public function toString(): string
    {
        $output = $this->_address;

        if (!empty($this->_name)) {
            $output = '"' . $this->_name . '" <' . $output . '>';
        }

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
    }
}
