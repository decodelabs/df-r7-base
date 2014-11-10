<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\mesh;
    
class Enum extends Base implements core\validate\IEnumField {

    use core\validate\TOptionProviderField;
    
    protected $_type = null;

    public function setType($type) {
        if($type !== null) {
            if(is_string($type) && false === strpos($type, '://')) {
                $type = 'type://'.$type;
            }

            $type = mesh\Manager::getInstance()->fetchEntity($type);

            if($type instanceof core\lang\ITypeRef) {
                $type->checkType('core/lang/IEnum');
            } else if(!$type instanceof core\lang\IEnumFactory) {
                throw new core\validate\InvalidArgumentException(
                    'Type cannot provide an enum'
                );
            }
        }

        $this->_type = $type;
        return $this;
    }

    public function getType() {
        return $this->_type;
    }

    

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        if($this->_type) {
            try {
                $value = $this->_type->factory($value);
            } catch(core\InvalidArgumentException $e) {
                $this->_applyMessage($node, 'invalid', $this->_handler->_(
                    'Please select a valid option'
                ));
            }
        } else {
            if(isset($this->_options[$value])) {
                $value = (string)$this->_options[$value];
                $this->_shouldSanitize = false;
            }

            if(!in_array($value, $this->_options)) {
                $this->_applyMessage($node, 'invalid', $this->_handler->_(
                    'Please select a valid option'
                ));
            }
        }

        return $this->_finalize($node, $value);
    }
}