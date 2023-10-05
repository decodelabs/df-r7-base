<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\uri;

use DecodeLabs\Glitch\Dumpable;

use df\core;

class Url implements IGenericUrl, Dumpable
{
    use core\TStringProvider;
    use TUrl_TransientScheme;
    use TUrl_PathContainer;
    use TUrl_QueryContainer;
    use TUrl_FragmentContainer;


    public static function factory($url)
    {
        if ($url instanceof IUrl) {
            return $url;
        }

        $class = get_called_class();
        return new $class($url);
    }

    public function __construct($url = null)
    {
        $this->import($url);
    }

    public function import($url = '')
    {
        if ($url !== null) {
            $this->reset();
        }

        if ($url == '' || $url === null) {
            return $this;
        }

        if ($url instanceof self) {
            $this->_scheme = $url->_scheme;

            if ($url->_path !== null) {
                $this->_path = clone $url->_path;
            }

            if ($url->_query !== null) {
                $this->_query = clone $url->_query;
            }

            $this->_fragment = $url->_fragment;

            return $this;
        }


        // Fragment
        $parts = explode('#', $url, 2);
        $url = (string)array_shift($parts);
        $this->setFragment(array_shift($parts));

        // Query
        $parts = explode('?', $url, 2);
        $url = (string)array_shift($parts);
        $this->setQuery(array_shift($parts));

        // Scheme
        $parts = explode('://', $url, 2);
        $url = (string)array_pop($parts);
        $this->setScheme(array_shift($parts));

        if (!empty($url)) {
            $this->setPath($url);
        }

        return $this;
    }

    public function reset(): static
    {
        $this->_resetScheme();
        $this->_resetPath();
        $this->_resetQuery();
        $this->_resetFragment();

        return $this;
    }

    public function __clone()
    {
        $this->_clonePath();
        $this->_cloneQuery();
    }

    public function __get($member)
    {
        switch ($member) {
            case 'scheme':
                return $this->getScheme();

            case 'path':
                return $this->getPath();

            case 'query':
                return $this->getQuery();

            case 'fragment':
                return $this->getFragment();
        }
    }

    public function __set($member, $value): void
    {
        switch ($member) {
            case 'scheme':
                $this->setScheme($value);
                return;

            case 'path':
                $this->setPath($value);
                return;

            case 'query':
                $this->setQuery($value);
                return;

            case 'fragment':
                $this->setFragment($value);
                return;
        }
    }




    // Strings
    public function toString(): string
    {
        if ($this->isJustFragment()) {
            return $this->_getFragmentString();
        }

        $output = '';
        $output .= $this->_getSchemeString();
        $output .= $this->_getPathString(true);
        $output .= $this->_getQueryString();
        $output .= $this->_getFragmentString();

        return $output;
    }

    public function toReadableString()
    {
        return $this->toString();
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
        yield 'classMembers' => [];
        yield 'section:properties' => false;
    }
}
