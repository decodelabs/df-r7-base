<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\constraint;

use df\core;
use df\opal;

use DecodeLabs\Glitch\Dumpable;

class Trigger implements opal\rdbms\schema\ITrigger, Dumpable
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
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $output = $this->_name;
        $output .= ' '.$this->getTimingName();
        $output .= ' '.$this->getEventName().' '.implode('; ', $this->_statements);
        $output .= ' ['.$this->_sqlVariant.']';

        yield 'definition' => $output;
    }
}
