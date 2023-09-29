<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\schema\constraint;

use df\opal;

class ForeignKey implements opal\rdbms\schema\IForeignKey
{
    use opal\schema\TConstraint_ForeignKey;
    use opal\rdbms\schema\TSqlVariantAware;


    public function __construct(opal\rdbms\schema\ISchema $schema, $name, $targetSchema)
    {
        $this->_setName($name);
        $this->setTargetSchema($targetSchema);
        $this->_sqlVariant = $schema->getSqlVariant();
    }

    protected function _normalizeAction($action)
    {
        return strtoupper((string)$action);
    }
}
