<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\schema\constraint;

use DecodeLabs\Glitch\Dumpable;
use df\axis;

use df\opal;

class Index implements opal\schema\IIndex, Dumpable
{
    use opal\schema\TConstraint_Index;

    public static function fromStorageArray(axis\schema\ISchema $schema, array $data)
    {
        $output = new self($schema, $data['nam']);
        $output->_setGenericStorageArray($schema, $data);
        return $output;
    }

    public function __construct(
        axis\schema\ISchema $schema,
        $name,
        $fields = null
    ) {
        $this->_setName($name);
        $this->setFields($fields);
        $schema->getName();
    }
}
