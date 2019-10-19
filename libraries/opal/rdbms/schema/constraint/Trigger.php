<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\constraint;

use df\core;
use df\opal;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Trigger implements opal\rdbms\schema\ITrigger, Inspectable
{
    use opal\schema\TConstraint_Trigger;
    use opal\schema\TConstraint_CharacterSetAware;
    use opal\schema\TConstraint_CollationAware;
    use opal\rdbms\schema\TSqlVariantAware;

    public function __construct(opal\rdbms\schema\ISchema $schema, $name, $event, $timing, $statements)
    {
        $this->_setName($name);
        $this->setEvent($event);
        $this->setTiming($timing);
        $this->setStatements($statements);
        $this->_sqlVariant = $schema->getSqlVariant();
    }

    protected function _hasFieldReference(array $fields)
    {
        $regex = '/(OLD|NEW)?\.('.implode('|', $fields).')/i';

        foreach ($this->_statements as $statement) {
            if (preg_match($regex, $statement)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $output = $this->_name;
        $output .= ' '.$this->getTimingName();
        $output .= ' '.$this->getEventName().' '.implode('; ', $this->_statements);
        $output .= ' ['.$this->_sqlVariant.']';

        $entity->setDefinition($output);
    }
}
