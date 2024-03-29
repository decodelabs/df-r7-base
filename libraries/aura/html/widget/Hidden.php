<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;

use df\arch;

class Hidden extends Base implements IFormDataWidget, Dumpable
{
    use TWidget_FormData;

    public const PRIMARY_TAG = 'input.hidden';
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

        $tag->setAttribute('type', 'hidden');
        $this->_applyFormDataAttributes($tag);

        return $tag;
    }
}
