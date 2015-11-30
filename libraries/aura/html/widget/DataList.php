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

class DataList extends Base implements IUngroupedOptionWidget, core\IDumpable {

    use TWidget_UngroupedSelectionInput;

    const PRIMARY_TAG = 'datalist';

    protected $_idDataAttribute = 'id';

    public function __construct(arch\IContext $context, $id, $options=null) {
        parent::__construct($context);

        $this->setId($id);

        if($options !== null) {
            $this->addOptions($options);
        }
    }

    public function setIdDataAttribute($attr) {
        $this->_idDataAttribute = $attr;
        return $this;
    }

    public function getIdDataAttribute() {
        return $this->_idDataAttribute;
    }

    protected function _render() {
        $tag = $this->getTag();
        $optionList = new aura\html\ElementContent();

        foreach($this->_options as $key => $label) {
            $option = new aura\html\Element('option');

            if($optionRenderer = $this->_optionRenderer) {
                $optionRenderer($option, $value, $label);
            } else {
                $option->setAttribute('value', $label);
            }

            if($this->_idDataAttribute) {
                $option->setDataAttribute($this->_idDataAttribute, $key);
            }

            $optionList->push($option->render());
        }

        return $tag->renderWith($optionList, true);
    }


// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->getId(),
            'options' => $this->_options,
            'tag' => $this->getTag()
        ];
    }
}