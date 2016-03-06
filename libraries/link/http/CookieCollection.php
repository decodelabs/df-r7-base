<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http;

use df;
use df\core;
use df\link;

class CookieCollection implements ICookieCollection, core\collection\IMappedCollection, core\IDumpable {

    use core\TStringProvider;
    use core\TValueMap;
    use core\collection\TValueMapArrayAccess;
    use core\collection\TExtractList;

    protected $_set = [];
    protected $_remove = [];

    public function __construct(...$input) {
        if(!empty($input)) {
            $this->import(...$input);
        }
    }

    public function __clone() {
        foreach($this->_set as $key => $cookie) {
            $this->_set[$key] = clone $cookie;
        }

        foreach($this->_remove as $key => $cookie) {
            $this->_remove[$key] = clone $cookie;
        }

        return $this;
    }

    public function import(...$input) {
        foreach($input as $data) {
            if($data instanceof core\collection\IHeaderMap) {
                $data = $data->get('Set-Cookie');

                if($data !== null && !is_array($data)) {
                    $data = [$data];
                }
            }

            if($data instanceof ICookieCollection) {
                foreach($data->_set as $key => $cookie) {
                    $this->_set[$key] = clone $cookie;
                }

                foreach($data->_remove as $key => $cookie) {
                    $this->_remove[$key] = clone $cookie;
                }
            } else if(core\collection\Util::isIterable($data)) {
                foreach($data as $key => $value) {
                    if(is_numeric($key)) {
                        $key = Cookie::fromString($value);
                        $value = null;
                    }

                    $this->set($key, $value);
                }
            }
        }

        return $this;
    }

    public function isEmpty($includeRemoved=true) {
        $output = empty($this->_set);

        if($includeRemoved) {
            $output = $output && empty($this->_remove);
        }

        return $output;
    }

    public function clear() {
        $this->_set = [];
        $this->_remove = [];
    }

    public function extract() {
        return array_shift($this->_set);
    }

    public function count() {
        return count($this->_set);
    }

    public function toArray() {
        return $this->_set;
    }

    public function getRemoved() {
        return $this->_remove;
    }


    public function set($name, $cookie=null) {
        if($name instanceof ICookie) {
            $cookie = $name;
            $name = $cookie->getName();
        }

        if(!$cookie instanceof ICookie) {
            $cookie = new Cookie($name, $cookie);
        }

        $name = $cookie->getName();
        unset($this->_remove[$name]);

        $this->_set[$name] = $cookie;
        return $this;
    }

    public function get($name, $default=null) {
        if(isset($this->_set[$name])) {
            return $this->_set[$name];
        }

        if(!$default instanceof ICookie) {
            $default = new Cookie($name, $default);
        }

        return $default;
    }

    public function has(...$names) {
        foreach($names as $name) {
            if($name instanceof ICookie) {
                $name = $name->getName();
            }

            if(isset($this->_set[$name])) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$names) {
        foreach($names as $name) {
            $cookie = null;

            if($name instanceof ICookie) {
                $cookie = $name;
                $name = $cookie->getName();
            }

            if(isset($this->_set[$name])) {
                $cookie = $this->_set[$name];
            } else if($cookie === null) {
                $cookie = new Cookie($name, 'deleted');
            }

            unset($this->_set[$name]);
            $this->_remove[$name] = $cookie;
        }

        return $this;
    }


    public function applyTo(IResponseHeaderCollection $headers) {
        $cookies = $headers->get('Set-Cookie');

        if($cookies === null) {
            $cookies = [];
        } else if(!is_array($cookies)) {
            $cookies = [$cookies];
        }

        foreach($this->_set as $cookie) {
            $cookies[] = $cookie->toString();
        }

        foreach($this->_remove as $cookie) {
            $cookies[] = $cookie->toInvalidateString();
        }

        $headers->set('Set-Cookie', array_unique($cookies));
        return $this;
    }

    public function sanitize(IRequest $request) {
        foreach($this->_set as $cookie) {
            if(!$cookie->getDomain()) {
                $cookie->setDomain($request->url->getDomain());
            }
        }

        foreach($this->_remove as $cookie) {
            if(!$cookie->getDomain()) {
                $cookie->setDomain($request->url->getDomain());
            }
        }

        return $this;
    }

// Strings
    public function toString() {
        $output = [];

        foreach($this->_set as $cookie) {
            $output[] = 'Set-Cookie: '.$cookie->toString();
        }

        foreach($this->_remove as $cookie) {
            $output[] = 'Set-Cookie: '.$cookie->toInvalidateString();
        }

        return implode("\r\n", $output);
    }


// Dump
    public function getDumpProperties() {
        $output = [];

        foreach($this->_set as $cookie) {
            $output['+ '.$cookie->getName()] = $cookie->toString();
        }

        foreach($this->_remove as $cookie) {
            $output['- '.$cookie->getName()] = $cookie->toInvalidateString();
        }

        return $output;
    }
}