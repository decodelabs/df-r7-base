<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate\field;

use DecodeLabs\Tagged as Html;
use DecodeLabs\Tagged\Embed;
use df\core;

class AudioEmbed extends Base implements core\validate\IAudioEmbedField
{
    public function validate()
    {
        // Sanitize
        $value = trim((string)$this->data->getValue());
        $value = $this->_sanitizeValue($value);

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }


        // Validate
        try {
            $embed = Html::$embed->audio($value);
        } catch (Embed\Exception $e) {
            $this->addError('invalid', $this->validator->_(
                'This does not appear to be a valid audio embed'
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
