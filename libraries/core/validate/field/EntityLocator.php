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


// Validate
    public function validate() {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if(!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        try {
            $locator = mesh\entity\Locator::factory($value);
            $value = (string)$locator;
        } catch(mesh\entity\InvalidArgumentException $e) {
            $this->addError('invalid', $this->validator->_(
                'Please enter a valid entity locator'
            ));
        }


        // Finalize
        $value = $this->_applyCustomValidator($value);
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
