<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\css\processor;

use df;
use df\core;
use df\aura;

use DecodeLabs\Glitch;
use DecodeLabs\Terminus\Session;

abstract class Base implements aura\css\IProcessor
{
    public $settings;

    public static function factory($name, $settings=null): aura\css\IProcessor
    {
        if ($name instanceof aura\css\IProcessor) {
            if ($settings) {
                $name->getSettings()->import($settings);
            }

            return $name;
        }

        $class = 'df\\aura\\css\\processor\\'.ucfirst($name);

        if (!class_exists($class)) {
            throw Glitch::ENotFound(
                'Css processor '.$name.' could not be found'
            );
        }

        return new $class($settings);
    }

    public function __construct($settings=null)
    {
        $this->settings = core\collection\Tree::factory($settings);
    }

    public function getSettings(): core\collection\ITree
    {
        return $this->settings;
    }

    public function setup(?Session $session=null)
    {
    }
}
