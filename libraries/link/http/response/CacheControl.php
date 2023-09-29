<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\response;

use df\core;
use df\link;

class CacheControl implements link\http\ICacheControl
{
    use core\TStringProvider;

    protected $_access = null;
    protected $_noStore = false;
    protected $_noTransform = false;
    protected $_mustRevalidate = false;
    protected $_proxyRevalidate = false;
    protected $_expiration = null;
    protected $_sharedExpiration = null;

    public function __construct($string = null)
    {
        if (is_string($string)) {
            $parts = explode(',', $string);

            foreach ($parts as $part) {
                $part = strtolower(trim((string)$part));
                $value = null;

                if (false !== strpos($part, '=')) {
                    $value = explode('=', $part, 2);
                    $part = array_shift($value);
                    $value = array_shift($value);
                }

                switch ($part) {
                    case 'public':
                    case 'private':
                    case 'no-cache':
                        $this->setAccess($part);
                        break;

                    case 'no-store':
                        $this->canStore(false);
                        break;

                    case 'no-transform':
                        $this->canTransform(false);
                        break;

                    case 'must-revalidate':
                        $this->shouldRevalidate(true);
                        break;

                    case 'proxy-revalidate':
                        $this->shouldRevalidateProxy(true);
                        break;

                    case 'max-age':
                        $this->setExpiration(new core\time\Duration($value));
                        break;

                    case 's-maxage':
                        $this->setSharedExpiration(new core\time\Duration($value));
                        break;
                }
            }
        }
    }

    public function setAccess($access)
    {
        if (is_string($access)) {
            $access = strtolower($access);
        }

        switch ($access) {
            case 'public':
            case 'private':
            case 'no-cache':
                $this->_access = $access;
                break;

            case false:
            case null:
                $this->_access = null;
                break;
        }

        return $this;
    }

    public function getAccess()
    {
        return $this->_access;
    }

    public function canStore(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_noStore = !$flag;
            return $this;
        }

        return !$this->_noStore;
    }

    public function canTransform(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_noTransform = !$flag;
            return $this;
        }

        return !$this->_noTransform;
    }

    public function shouldRevalidate(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_mustRevalidate = $flag;
            return $this;
        }

        return $this->_mustRevalidate;
    }

    public function shouldRevalidateProxy(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_proxyRevalidate = $flag;
            return $this;
        }

        return $this->_proxyRevalidate;
    }

    public function setExpiration($duration = null)
    {
        if ($duration !== null) {
            $duration = core\time\Duration::factory($duration);
        }

        $this->_expiration = $duration;
        return $this;
    }

    public function getExpiration()
    {
        return $this->_expiration;
    }

    public function setSharedExpiration($duration = null)
    {
        if ($duration !== null) {
            $duration = core\time\Duration::factory($duration);
        }

        $this->_sharedExpiration = $duration;
        return $this;
    }

    public function getSharedExpiration()
    {
        return $this->_sharedExpiration;
    }

    public function toString(): string
    {
        $output = [];

        if ($this->_access !== null) {
            $output[] = $this->_access;
        }

        if ($this->_noStore) {
            $output[] = 'no-store';
        }

        if ($this->_noTransform) {
            $output[] = 'no-transform';
        }

        if ($this->_mustRevalidate) {
            $output[] = 'must-revalidate';
        }

        if ($this->_proxyRevalidate) {
            $output[] = 'proxy-revalidate';
        }

        if ($this->_expiration) {
            $output[] = 'max-age=' . $this->_expiration->getSeconds();
        }

        if ($this->_sharedExpiration) {
            $output[] = 's-maxage=' . $this->_sharedExpiration->getSeconds();
        }

        return implode(', ', $output);
    }

    public function clear()
    {
        $this->_access = null;
        $this->_noStore = false;
        $this->_noTransform = false;
        $this->_mustRevalidate = false;
        $this->_proxyRevalidate = false;
        $this->_expiration = null;
        $this->_sharedExpiration = null;

        return $this;
    }
}
