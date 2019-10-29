<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\fortify;

use df;
use df\core;
use df\axis;
use df\opal;

use DecodeLabs\Terminus\Cli;
use DecodeLabs\Glitch;

abstract class Base implements IFortify
{
    use core\TContextProxy;

    protected $_unit;
    protected $_model;

    public static function loadAll(axis\IUnit $unit): \Generator
    {
        $path = 'apex/models/'.$unit->getModel()->getModelName().'/'.$unit->getUnitName().'/fortify';

        foreach (df\Launchpad::$loader->lookupClassList($path, false) as $name => $class) {
            if (!class_exists($class)) {
                continue;
            }

            $ref = new \ReflectionClass($class);

            if ($ref->isAbstract() || !$ref->implementsInterface('df\\axis\\fortify\\IFortify')) {
                continue;
            }

            yield $name => new $class($unit);
        }
    }

    public static function factory(axis\IUnit $unit, string $name): IFortify
    {
        $modelName = $unit->getModel()->getModelName();
        $unitName = $unit->getUnitName();

        $class = 'df\\apex\\models\\'.$modelName.'\\'.$unitName.'\\fortify\\'.ucfirst($name);

        if (!class_exists($class)) {
            throw Glitch::ENotFound(
                'Unit fortify task '.$modelName.'/'.$unitName.'/'.ucfirst($name).' could not be found'
            );
        }

        return new $class($unit);
    }

    public function __construct(axis\IUnit $unit)
    {
        $this->_unit = $unit;
        $this->_model = $unit->getModel();
        $this->context = $unit->context;
    }

    public function getUnit(): axis\IUnit
    {
        return $this->_unit;
    }

    public function getModel(): axis\IModel
    {
        return $this->_model;
    }

    public function getName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    final public function dispatch()
    {
        $ret = $this->execute();

        if ($ret instanceof \Generator) {
            foreach ($ret as $key => $value) {
                if ($key === true) {
                    Cli::writeLine($value);
                } else {
                    Cli::write($value);
                }
            }
        }

        return $this;
    }

    abstract protected function execute();
}
