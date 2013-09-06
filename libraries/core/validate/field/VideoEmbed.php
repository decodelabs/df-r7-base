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

    public function validate(core\collection\IInputTree $node) {
        $value = trim($node->getValue());

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        try {
            $embed = spur\video\Embed::parse($value);
        } catch(spur\video\IException $e) {
            $node->addError('invalid', $this->_(
                'This does not appear to be a valid video embed'
            ));
        }

        return $this->_finalize($node, $value);
    }
}