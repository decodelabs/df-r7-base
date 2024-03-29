<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df\core;
use df\mesh;

class EntityLocator extends Base implements core\validate\IEntityLocatorField
{
    public function validate()
    {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        try {
            $locator = mesh\entity\Locator::factory($value);
            $value = (string)$locator;
        } catch (mesh\entity\Exception $e) {
            $this->addError('invalid', $this->validator->_(
                'Please enter a valid entity locator'
            ));
        }


        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
