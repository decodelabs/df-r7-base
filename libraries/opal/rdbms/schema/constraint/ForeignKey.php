<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\constraint;

use df\core;
use df\opal;

class ForeignKey implements opal\rdbms\schema\IForeignKey {
    
    use opal\schema\TConstraint_ForeignKey;
    use opal\rdbms\schema\TSqlVariantAware;
    
    
    public function __construct(opal\rdbms\schema\ISchema $schema, $name, $targetSchema) {
        parent::__construct($name, $targetSchema);
        $this->_sqlVariant = $schema->getSqlVariant();
    }
    
    protected function _normalizeAction($action) {
        return strtoupper($action);
    }
    
// Dump
    public function getDumpProperties() {
        return parent::getDumpProperties().' ['.$this->_sqlVariant.']';
    }
}
