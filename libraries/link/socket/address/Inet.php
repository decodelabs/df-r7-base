<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\socket\address;

use df;
use df\core;
use df\link;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Inet extends Base implements IInetAddress, Inspectable
{
    use core\uri\TUrl_IpContainer;
    use core\uri\TUrl_PortContainer;


    public static function factory($address)
    {
        if ($address instanceof IInetAddress) {
            return $address;
        }

        return new self($address);
    }

    public function import($address='')
    {
        if ($address !== null) {
            $this->reset();
        }

        if ($address == '' || $address === null) {
            return $this;
        }

        if ($address instanceof IInetAddress) {
            $this->_scheme = $address->_scheme;
            $this->_ip = $address->_ip;
            $this->_port = $address->_ip;

            return $this;
        }


        $parts = explode('://', $address, 2);

        if (false !== strpos($address, '.')
        || false !== strpos($address, ':')) {
            $address = array_pop($parts);
            $scheme = array_shift($parts);
        } else {
            $scheme = array_shift($parts);
            $address = array_pop($parts);
        }

        if ($convertToV6 = (substr($scheme, -1) == '6')) {
            $scheme = substr($scheme, 0, -1);
        }

        $this->setScheme($scheme);

        if (isset($address{0}) && $address{0} == '[') {
            // V6
            $parts = explode(']', substr($address, 1), 2);
            $this->setIp(array_shift($parts));

            if (isset($parts[0])) {
                $this->setPort(substr(array_shift($parts), 1));
            }
        } else {
            $parts = explode(':', $address, 2);
            $ip = array_shift($parts);

            if (empty($ip)) {
                $ip = '0.0.0.0';
            }

            if (preg_match('/[^0-9.]/', $ip)) {
                $ip = gethostbyname($ip);
            }

            $this->setIp($ip);

            if (isset($parts[0])) {
                $this->setPort(array_shift($parts));
            }

            if ($convertToV6) {
                $this->_ip->convertToV6();
            }
        }

        return $this;
    }

    public function reset()
    {
        $this->_resetScheme();
        $this->_resetIp();
        $this->_resetPort();

        return $this;
    }


    public function __get($member)
    {
        switch ($member) {
            case 'scheme':
                return $this->getScheme();

            case 'ip':
                return $this->getIp();

            case 'port':
                return $this->getPort();
        }
    }

    public function __set($member, $value)
    {
        switch ($member) {
            case 'scheme':
                return $this->setScheme($value);

            case 'ip':
                return $this->setIp($value);

            case 'port':
                return $this->setPort($value);
        }
    }


    // Scheme
    public function setScheme($scheme)
    {
        if (!strlen($scheme)) {
            $scheme = 'tcp';
        }

        $scheme = strtolower($scheme);

        switch ($scheme) {
            case 'udp':
            case 'tcp':
            case 'icmp':
                $this->_scheme = $scheme;
                break;

            case 'ssl':
            case 'sslv2':
            case 'sslv3':
            case 'tls':
                $this->_scheme = 'tcp';
                $this->setSecureTransport($scheme);
                break;

            default:
                if (false == getprotobyname($scheme)) {
                    throw new link\socket\InvalidArgumentException(
                        'Protocol '.$scheme.' is not currently supported'
                    );
                }

                $this->_scheme = $scheme;
                break;
        }

        return $this;
    }


    // Type
    public function getSocketDomain()
    {
        if ($this->getIp()->isV6()) {
            return 'inet6';
        } else {
            return 'inet';
        }
    }

    public function getDefaultSocketType()
    {
        if ($this->_scheme == 'tcp') {
            return 'stream';
        } elseif ($this->_scheme == 'udp') {
            return 'datagram';
        } else {
            return 'raw';
        }
    }



    // Strings
    public function toString($scheme=null): string
    {
        if ($scheme === null) {
            $scheme = $this->_scheme;

            if (!$scheme) {
                $scheme = 'tcp';
            }

            if ($scheme == 'tcp' && $this->_secureTransport) {
                $scheme = $this->_secureTransport;
            }
        }

        $output = $scheme.'://';
        $output .= $this->_getIpString();
        $output .= $this->_getPortString();

        return $output;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->toString());
    }
}
