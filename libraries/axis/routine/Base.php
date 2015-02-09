<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\routine;

use df;
use df\core;
use df\axis;

abstract class Base implements IRoutine {
    
    use core\TContextProxy;

    public $io;

    protected $_unit;
    protected $_model;

    public static function factory(axis\IUnit $unit, $name, core\io\IMultiplexer $multiplexer=null) {
        $modelName = $unit->getModel()->getModelName();
        $unitName = $unit->getUnitName();

        $class = 'df\\apex\\models\\'.$modelName.'\\'.$unitName.'\\routines\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new RuntimeException(
                'Unit routine '.$modelName.'/'.$unitName.'/'.ucfirst($name).' could not be found'
            );
        }

        return new $class($unit, $multiplexer);
    }

    public function __construct(axis\IUnit $unit, core\io\IMultiplexer $multiplexer=null) {
        $this->_unit = $unit;
        $this->_model = $unit->getModel();
        $this->context = $unit->context;

        if($multiplexer) {
            $this->setMultiplexer($multiplexer);
        }
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

    public function setMultiplexer(core\io\IMultiplexer $multiplexer) {
        $this->io = $multiplexer;
        return $this;
    }

    public function getMultiplexer() {
        if(!$this->io) {
            if($this->getRunMode() == 'Task') {
                $this->io = $this->task->getSharedIo();
            } else {
                $this->io = core\io\Multiplexer::defaultFactory('memory');
            }
        }

        return $this->io;
    }

    public function execute() {
        $this->getMultiplexer();
        $args = func_get_args();

        if(!method_exists($this, '_execute')) {
            throw new LogicException(
                'Unit routine '.$this->_unit->getUnitId().'/'.$this->getName().' does not implement _execute method'
            );
        }

        call_user_func_array([$this, '_execute'], $args);
        return $this;
    }
}
