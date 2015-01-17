<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\spur;

class VideoEmbed extends Base implements core\validate\IVideoEmbedField {

    use core\validate\TSanitizingField;

    public function validate(core\collection\IInputTree $node) {
        $value = trim($node->getValue());
        $value = $this->_sanitizeValue($value);

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        try {
            $embed = spur\video\Embed::parse($value);
        } catch(spur\video\IException $e) {
            $this->_applyMessage($node, 'invalid', $this->_(
                'This does not appear to be a valid video embed'
            ));
        }

        if($node->isValid()) {
            if($this->_requireGroup !== null && !$this->_handler->checkRequireGroup($this->_requireGroup)) {
                $this->_handler->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else {
            if($this->_requireGroup !== null) {
                $this->_handler->setRequireGroupFulfilled($this->_requireGroup);
            }
        }

        return $this->_finalize($node, $value);
    }
}