<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\constraint;

use df\core;
use df\opal;
use df\axis;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Index implements opal\schema\IIndex, Inspectable
{
    use opal\schema\TConstraint_Index;

    public static function fromStorageArray(opal\schema\ISchema $schema, array $data)
    {
        $output = new self($schema, $data['nam']);
        $output->_setGenericStorageArray($schema, $data);
        return $output;
    }

    public function __construct(axis\schema\ISchema $schema, $name, $fields=null)
    {
        $schema;
        $this->_setName($name);
        $this->setFields($fields);
    }
}
