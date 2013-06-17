<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
    
class Enum extends Base implements core\validate\IEnumField {

    protected $_options = array();

    public function setOptions(array $options) {
        $this->_options = $options;
        return $this;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $eValue = $node->getValue();

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        if(isset($this->_options[$value])) {
            $eValue = $this->_options[$value];
            $this->_shouldSanitize = false;
        }

        if(!in_array($eValue, $this->_options)) {
            $node->addError('invalid', $this->_handler->_(
                'Please select a valid option'// from %o%', 
                //['%o%' => implode(', ', $this->_options)]
            ));
        }

        return $this->_finalize($node, $eValue);
    }
}