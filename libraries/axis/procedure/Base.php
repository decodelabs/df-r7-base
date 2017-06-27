<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\procedure;

use df;
use df\core;
use df\axis;

abstract class Base implements IProcedure {

    use core\TContextProxy;

    public $values;
    public $validator;

    protected $_unit;
    protected $_model;
    protected $_isPrepared = false;

    public static function factory(axis\IUnit $unit, $name, $values, $item=null) {
        $modelName = $unit->getModel()->getModelName();
        $unitName = $unit->getUnitName();

        $class = 'df\\apex\\models\\'.$modelName.'\\'.$unitName.'\\procedures\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new RuntimeException(
                'Unit procedure '.$modelName.'/'.$unitName.'/'.ucfirst($name).' could not be found'
            );
        }

        return new $class($unit, $values, $item);
    }

    public function __construct(axis\IUnit $unit, $values) {
        $this->_unit = $unit;
        $this->_model = $unit->getModel();
        $this->context = $unit->context;
        $this->setValues($values);
        $this->validator = new core\validate\Handler();
    }

    public function getUnit() {
        return $this->_unit;
    }

    public function getModel() {
        return $this->_model;
    }

    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function setValues($values) {
        if(!$values instanceof core\collection\IInputTree) {
            $values = core\collection\InputTree::factory($values);
        }

        $this->values = $values;
        return $this;
    }

    public function getValues() {
        return $this->values;
    }

    public function setDataMap(array $map) {
        $this->validator->setDataMap($map);
        return $this;
    }

    public function getDataMap() {
        return $this->validator->getDataMap();
    }

    public function execute(...$args) {
        if(!$this->_isPrepared) {
            $this->prepare();
        }

        if(!method_exists($this, '_execute')) {
            throw new LogicException(
                'Unit procedure '.$this->_unit->getUnitId().'/'.$this->getName().' does not implement _execute method'
            );
        }

        $this->_execute(...$args);
        return $this->isValid();
    }

    public function validate() {
        if(!$this->_isPrepared) {
            $this->prepare();
        }

        $this->validator->validate($this->values);
        return $this->isValid();
    }

    public function prepare() {
        $this->_prepareValidator();
        $this->_prepare();
        $this->_isPrepared = true;
        return $this;
    }

    protected function _prepare() {}

    protected function _prepareValidator() {
        $this->_unit->prepareValidator($this->validator);
    }

    public function isValid(): bool {
        return $this->values->isValid() && $this->validator->isValid();
    }
}
