<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\opal;
use df\mesh;

class EntityLocator extends Base implements core\validate\IEntityLocatorField {
    
    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);
        
        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }
        
        try {
            $locator = mesh\entity\Locator::factory($value);
            $value = (string)$locator;
        } catch(mesh\entity\InvalidArgumentException $e) {
            $this->_applyMessage($node, 'invalid', $this->validator->_(
                'Please enter a valid entity locator'
            ));
        }

        return $this->_finalize($node, $value);
    }
}
