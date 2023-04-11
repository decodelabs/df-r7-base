<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;
use df\arch;

use df\core;

class FileUpload extends Base implements IFileUploadWidget, Dumpable
{
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_OptionalMultipleValueInput;
    use core\lang\TAcceptTypeProcessor;

    public const PRIMARY_TAG = 'input.picker.file';
    public const ARRAY_INPUT = false;

    public function __construct(arch\IContext $context, $name, $value = null)
    {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);
    }

    protected function _render()
    {
        $tag = $this->getTag();
        $tag->setAttribute('type', 'file');

        $this->_applyFormDataAttributes($tag, false);
        $this->_applyInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyOptionalMultipleValueInputAttributes($tag);

        if (!empty($this->_acceptTypes)) {
            $tag->setAttribute('accept', implode(',', $this->_acceptTypes));
        }

        return $tag;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*name' => $this->_name,
            '%tag' => $this->getTag()
        ];
    }
}
