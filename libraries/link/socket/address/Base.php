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

abstract class Base implements IAddress
{
    use core\TStringProvider;
    use core\uri\TUrl_TransientScheme;

    protected $_secureTransport = null;

    public static function factory($address)
    {
        if ($address instanceof IAddress) {
            return $address;
        }

        $parts = explode('://', $address, 2);

        $temp = array_pop($parts);
        $proto = strtolower(array_shift($parts));

        if (!strlen($proto)) {
            if (false !== stristr(str_replace('\\', '/', $temp), '/')) {
                $proto = 'unix';
            } else {
                $proto = 'tcp';
            }
        }

        if ($proto == 'unix' || $proto == 'udg') {
            return new Unix($address);
        } else {
            return new Inet($address);
        }
    }

    public function __construct($url=null)
    {
        if ($url !== null) {
            $this->import($url);
        }
    }

    public function setSecureTransport($transport)
    {
        $this->_secureTransport = strtolower($transport);
        return $this;
    }

    public function getSecureTransport()
    {
        return $this->_secureTransport;
    }

    public function toReadableString()
    {
        return $this->toString();
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->toString());
    }
}
