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

class DataList extends Base implements IUngroupedOptionWidget, Inspectable
{
    use TWidget_UngroupedSelectionInput;

    const PRIMARY_TAG = 'datalist';

    protected $_idDataAttribute = 'id';

    public function __construct(arch\IContext $context, $id, $options=null)
    {
        parent::__construct($context);

        $this->setId($id);

        if ($options !== null) {
            $this->addOptions($options);
        }
    }

    public function setIdDataAttribute($attr)
    {
        $this->_idDataAttribute = $attr;
        return $this;
    }

    public function getIdDataAttribute()
    {
        return $this->_idDataAttribute;
    }

    protected function _render()
    {
        $tag = $this->getTag();
        $optionList = new aura\html\ElementContent();

        foreach ($this->_options as $key => $label) {
            $option = new aura\html\Element('option');

            if ($optionRenderer = $this->_optionRenderer) {
                $optionRenderer($option, $value, $label);
            } else {
                $option->setAttribute('value', $label);
            }

            if ($this->_idDataAttribute) {
                $option->setDataAttribute($this->_idDataAttribute, $key);
            }

            $optionList->push($option->render());
        }

        return $tag->renderWith($optionList, true);
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*id' => $inspector($this->getId()),
                '%tag' => $inspector($this->getTag())
            ])
            ->setValues($inspector->inspectList($this->_options));
    }
}
