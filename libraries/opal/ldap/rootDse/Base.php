<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap\rootDse;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Base implements opal\ldap\IRootDse, Inspectable
{
    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_ScalarSortable;
    use core\collection\TArrayCollection_AssociativeValueMap;
    use core\collection\TArrayCollection_Seekable;
    use core\collection\TArrayCollection_MappedMovable;

    public static function factory(opal\ldap\IAdapter $adapter, array $data)
    {
        $class = 'df\\opal\\ldap\\rootDse\\'.$adapter->getConnection()->getType();

        if (!class_exists($class)) {
            $class = __CLASS__;
        }

        return new $class($data);
    }

    public function __construct(array $data)
    {
        $this->import($data);
    }

    public function getReductiveIterator(): \Iterator
    {
        return new core\collection\ReductiveMapIterator($this);
    }


    public function getNamingContexts()
    {
        $output = [];

        foreach ($this['namingContexts'] as $dn) {
            $output[] = opal\ldap\Dn::factory($dn);
        }

        return $output;
    }

    public function getSubschemaSubentry()
    {
        return opal\ldap\Dn::factory($this['subschemaSubentry']);
    }

    public function supportsVersion($version)
    {
        return in_array($version, $this['supportedLDAPVersion']);
    }

    public function supportsSaslMechanism($mechanism)
    {
        return in_array(strtoupper($mechanism), $this['supportedSASLMechanisms']);
    }

    public function getSchemaDn()
    {
        return $this->getSubschemaSubentry();
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setValues($inspector->inspectList($this->_collection));
    }
}
