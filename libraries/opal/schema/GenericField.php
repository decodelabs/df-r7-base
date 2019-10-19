<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\schema;

use df;
use df\core;
use df\opal;

class GenericField implements opal\schema\IField
{
    use opal\schema\TField;

    public function __construct($name, array $args=[])
    {
        $this->_setName($name);
        empty($args);
    }
}
