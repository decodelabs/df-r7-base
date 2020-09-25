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

use DecodeLabs\Glitch\Dumpable;

class Hidden extends Base implements IFormDataWidget, Dumpable
{
    use TWidget_FormData;

    const PRIMARY_TAG = 'input.hidden';
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

        $tag->setAttribute('type', 'hidden');
        $this->_applyFormDataAttributes($tag);

        return $tag;
    }
}
