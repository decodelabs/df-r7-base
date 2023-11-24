<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;
use Stringable;

class Dsn implements IDsn, Dumpable
{
    use core\TStringProvider;

    protected $_adapter;
    protected $_username;
    protected $_password;
    protected $_protocol;
    protected $_hostname;
    protected $_port;
    protected $_socket;
    protected $_database;
    protected $_databaseSuffix;
    protected $_options = [];

    protected $_hash;
    protected $_serverHash;

    public static function factory($dsn)
    {
        if ($dsn instanceof IDsn) {
            return $dsn;
        }

        return new self($dsn);
    }

    public function __construct($dsn = null)
    {
        if ($dsn !== null) {
            $this->_parse($dsn);
        }
    }

    protected function _parse($dsn)
    {
        if (!is_string($dsn)) {
            if ($dsn instanceof Stringable) {
                $dsn = $dsn->__toString();
            } else {
                throw Exceptional::InvalidArgument(
                    'Invalid dsn string!'
                );
            }
        }

        $regex = "!^(([a-z0-9-_]+)(\(([^()]+)\))?)(://((((([^@/:]+)(:([^@/]+))?)@)?((([a-z]+)\((([^?():]+)(:([^()?]+))?)\))|((([^/?:]+)(:([^/?]+))?))))/?)?([^?]+)?(\?(.+))?)?$!i";
        $matches = [];

        if (!preg_match($regex, (string)$dsn, $matches)) {
            throw Exceptional::InvalidArgument(
                'Invalid dsn string: ' . $dsn
            );
        }

        $this->_adapter = ucfirst(@$matches[2]);


        if (isset($matches[5])) {
            // Username
            if (!empty($matches[10])) {
                $this->_username = $matches[10];
            }

            // Password
            if (!empty($matches[12])) {
                $this->_password = $matches[12];
            }

            // Protocol / hostname etc
            if (!empty($matches[15])) {
                $this->_protocol = @$matches[15];

                if ($this->_protocol === 'unix') {
                    $this->_socket = @$matches[16];
                } else {
                    $this->_hostname = @$matches[17];
                    if (strlen($matches[19]) > 0) {
                        $this->_port = @$matches[19];
                    }
                }
            } elseif (!empty($matches[20])) {
                $this->_hostname = @$matches[22];

                if ((isset($matches[24]) && (strlen($matches[24]) > 0))) {
                    $this->_port = @$matches[24];
                }
            }

            // Database
            if (isset($matches[25]) && (strlen($matches[25]) > 0)) {
                $this->_database = @$matches[25];
            }

            // Query
            if (isset($matches[27]) && (strlen($matches[27]) > 0)) {
                $options = explode('&', $matches[27]);

                foreach ($options as $option) {
                    list($key, $value) = explode('=', $option);
                    $key = strtolower($key);

                    if (!isset($this->{'_' . $key})) {
                        $this->_options[$key] = urldecode($value);
                    }
                }
            }
        }

        if (!$this->_database) {
            if ($this->_hostname) {
                $this->_database = $this->_hostname;
                $this->_hostname = null;
            } else {
                $this->_database = 'default';
            }
        }
    }


    // Adapter
    public function setAdapter($adapter)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_adapter = $adapter;

        return $this;
    }

    public function getAdapter()
    {
        return $this->_adapter;
    }



    // Username
    public function setUsername($username)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_username = $username;

        return $this;
    }

    public function getUsername()
    {
        return $this->_username;
    }


    // Password
    public function setPassword($password)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_password = $password;

        return $this;
    }

    public function getPassword()
    {
        return $this->_password;
    }


    // Protocol
    public function setProtocol($protocol)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_protocol = $protocol;

        return $this;
    }

    public function getProtocol()
    {
        return $this->_protocol;
    }


    // Hostname
    public function setHostname($hostname)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_hostname = $hostname;

        return $this;
    }

    public function getHostname($default = 'localhost')
    {
        if (!$this->_hostname) {
            return $default;
        }

        return $this->_hostname;
    }


    // Port
    public function setPort($port)
    {
        if ($port !== null) {
            $port = (int)$port;
        }

        $this->_hash = null;
        $this->_serverHash = null;
        $this->_port = $port;

        return $this;
    }

    public function getPort()
    {
        return $this->_port;
    }


    // Socket
    public function setSocket($socket)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_socket = $socket;

        return $this;
    }

    public function getSocket()
    {
        return $this->_socket;
    }


    // Database
    public function setDatabase($database)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_database = $database;
        $this->_databaseSuffix = null;

        return $this;
    }

    public function getDatabase()
    {
        return $this->_database . $this->_databaseSuffix;
    }

    public function setDatabaseKeyName($name)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_database = $name;

        return $this;
    }

    public function getDatabaseKeyName()
    {
        return $this->_database;
    }

    public function setDatabaseSuffix($suffix)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_databaseSuffix = $suffix;
        return $this;
    }

    public function getDatabaseSuffix()
    {
        return $this->_databaseSuffix;
    }


    // Options
    public function setOption($key, $value)
    {
        $this->_hash = null;
        $this->_serverHash = null;
        $this->_options[$key] = $value;

        return $this;
    }

    public function getOption($key, $default = null)
    {
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        } else {
            return $default;
        }
    }


    // Hash
    public function getHash()
    {
        if ($this->_hash === null) {
            ksort($this->_options);
            $this->_hash = md5($this->toString());
        }

        return $this->_hash;
    }

    public function getServerHash()
    {
        if ($this->_serverHash === null) {
            ksort($this->_options);
            $this->_serverHash = md5($this->getServerString());
        }

        return $this->_serverHash;
    }


    // String
    public function toString(): string
    {
        if ($this->_adapter === null) {
            throw Exceptional::InvalidArgument(
                'Dsn must contain adapter value!'
            );
        }

        return $this->_adapter . '://' . $this->getConnectionString();
    }

    public function getConnectionString()
    {
        $output = $this->_getBaseServerString();
        $output .= $this->_getDatabaseString();
        $output .= $this->_getOptionString();

        return $output;
    }

    public function getServerString()
    {
        if ($this->_adapter === null) {
            throw Exceptional::InvalidArgument(
                'Dsn must contain adapter value!'
            );
        }

        $output = $this->_adapter . '://';
        $base = $this->_getBaseServerString();

        if (empty($base)) {
            $base = $this->_getDatabaseString();
        }

        $output .= $base;
        $output .= $this->_getOptionString();

        return $output;
    }

    protected function _getBaseServerString()
    {
        $output = '';

        if ($this->_username !== null || $this->_password !== null) {
            $output .= $this->_username . ':' . $this->_password . '@';
        }

        if ($this->_protocol !== null) {
            $output .= $this->_protocol . '(';

            if (strlen((string)$this->_socket)) {
                $output .= $this->_socket;
            } else {
                $output .= $this->_hostname;
            }

            $output .= ')';
        } elseif ($this->_hostname !== null) {
            $output .= $this->_hostname;

            if ($this->_port !== null) {
                $output .= ':' . $this->_port;
            }

            $output .= '/';
        }

        return $output;
    }

    protected function _getDatabaseString()
    {
        if ($this->_database === null) {
            throw Exceptional::InvalidArgument(
                'Dsn must contain database value!'
            );
        }

        $output = $this->_database;

        if ($this->_databaseSuffix) {
            $output .= $this->_databaseSuffix;
        }

        return $output;
    }

    protected function _getOptionString()
    {
        $output = '';

        if (!empty($this->_options)) {
            $output .= '?' . http_build_query($this->_options);
        }

        return $output;
    }

    public function getDisplayString($credentials = false)
    {
        $output = lcfirst($this->_adapter) . '://';

        if ($credentials && ($this->_username !== null || $this->_password !== null)) {
            $output .= $this->_username . ':****@';
        }

        if ($this->_protocol !== null) {
            $output .= $this->_protocol . '(';

            if (strlen((string)$this->_socket)) {
                $output .= $this->_socket;
            } else {
                $output .= $this->_hostname;
            }

            $output .= ')';
        } elseif ($this->_hostname !== null) {
            $output .= $this->_hostname;

            if ($this->_port !== null) {
                $output .= ':' . $this->_port;
            }

            $output .= '/';
        }

        if ($this->_database === null) {
            throw Exceptional::InvalidArgument(
                'Dsn must contain database value!'
            );
        }

        $output .= $this->_database;

        if ($this->_databaseSuffix) {
            $output .= $this->_databaseSuffix;
        }

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'definition' => $this->toString();
    }
}
