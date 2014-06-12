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

class CheckboxList extends Base implements core\IDumpable {
    
    const PRIMARY_TAG = 'div';

    protected $_labelClass = null;
    protected $_shouldWrapBody = true;
    protected $_values;
    protected $_options = [];
    protected $_context;

    public function __construct(arch\IContext $context, core\collection\IInputTree $values, array $options) {
        $this->setValues($values);
        $this->setOptions($options);
        $this->_context = $context;
    }

    public function setValues(core\collection\IInputTree $values) {
        $this->_values = $values;
        return $this;
    }

    public function getValues() {
        return $this->_values;
    }

    public function setOptions(array $options) {
        $this->_options = $options;
        return $this;
    }

    public function getOptions() {
        return $this->_options;
    }

    public function shouldWrapBody($flag=null) {
        if($flag !== null) {
            $this->_shouldWrapBody = (bool)$flag;
            return $this;
        }

        return $this->_shouldWrapBody;
    }

    public function setLabelClass($class) {
        $this->_labelClass = $class;
        return $this;
    }

    public function getLabelClass() {
        return $this->_labelClass;
    }

    public function isInline($flag=null) {
        if($flag !== null) {
            if($flag) {
                $this->getTag()->addClass('inline');
            } else {
                $this->getTag()->removeClass('inline');
            }

            return $this;
        } else {
            return $this->getTag()->hasClass('inline');
        }
    }

    protected function _render() {
        $tag = $this->getTag();
        $checkboxList = [];

        foreach($this->_options as $key => $label) {
            $checkboxList[] = self::factory($this->_context, 'Checkbox', [
                    $key, $this->_values->{$key}, $label
                ])
                ->shouldWrapBody($this->_shouldWrapBody)
                ->setLabelClass($this->_labelClass)
                ->setRenderTarget($this->getRenderTarget());
        }

        return $tag->renderWith($checkboxList);
    }


// Dump
    public function getDumpProperties() {
        return [
            'values' => $this->_values,
            'options' => $this->_options,
            'tag' => $this->getTag()
        ];
    }
}