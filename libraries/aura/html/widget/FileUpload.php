<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class FileUpload extends Base implements IFileUploadWidget, Inspectable
{
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_OptionalMultipleValueInput;
    use core\io\TAcceptTypeProcessor;

    const PRIMARY_TAG = 'input.picker.file';
    const ARRAY_INPUT = false;

    public function __construct(arch\IContext $context, $name, $value=null)
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
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*name' => $inspector($this->_name),
                '%tag' => $inspector($this->getTag())
            ]);
    }
}
