<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user;

use df;
use df\core;
use df\user;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class PostalAddress implements IPostalAddress, Inspectable
{
    use TPostalAddress;
    use core\TStringProvider;

    protected $_street1;
    protected $_street2;
    protected $_street3;
    protected $_locality;
    protected $_region;
    protected $_postalCode;
    protected $_countryCode;

    public static function fromArray(array $data)
    {
        $output = new self();
        $keys = ['street1', 'street2', 'street3', 'locality', 'region', 'postalCode', 'countryCode', 'countryName'];

        foreach ($data as $key => $value) {
            if (in_array($key, $keys)) {
                $output->{'_'.$key} = $value;
            }
        }

        return $output;
    }

    protected function __construct()
    {
    }

    public function getStreetLine1()
    {
        return $this->_street1;
    }

    public function getStreetLine2()
    {
        return $this->_street2;
    }

    public function getStreetLine3()
    {
        return $this->_street3;
    }

    public function getLocality()
    {
        return $this->_locality;
    }

    public function getRegion()
    {
        return $this->_region;
    }

    public function getPostalCode()
    {
        return $this->_postalCode;
    }

    public function getCountryCode()
    {
        return $this->_countryCode;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->toString());
    }
}
