<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\spur;

use DecodeLabs\Tagged as Html;
use DecodeLabs\Tagged\Embed;

class VideoEmbed extends Base implements core\validate\IVideoEmbedField
{
    public function validate()
    {
        // Sanitize
        $value = trim($this->data->getValue());
        $value = $this->_sanitizeValue($value);

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        try {
            $embed = Html::$embed->video($value);
        } catch (Embed\Exception $e) {
            $this->addError('invalid', $this->validator->_(
                'This does not appear to be a valid video embed'
            ));
        }


        // Finalize
        if ($this->data->isValid()) {
            if ($this->_requireGroup !== null && !$this->validator->checkRequireGroup($this->_requireGroup)) {
                $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else {
            if ($this->_requireGroup !== null) {
                $this->validator->setRequireGroupFulfilled($this->_requireGroup);
            }
        }

        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }
}
