<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\package;

use df;
use df\core;
use df\flex;
use df\iris;

abstract class Base extends iris\processor\Base implements flex\latex\IPackage {

    const COMMANDS = [];
    const ENVIRONMENTS = [];

    public static function getCommandList() {
        return static::COMMANDS;
    }

    public static function getEnvironmentList() {
        return static::ENVIRONMENTS;
    }

    public function getName() {
        return parent::getName().'Package';
    }

    public function initialize(iris\IParser $parser) {
        parent::initialize($parser);

        foreach(static::getCommandList() as $val) {
            $parser->registerCommand($val, $this);
        }

        foreach(static::getEnvironmentList() as $env) {
            $parser->registerEnvironment($env, $this);
        }

        return $this;
    }

    /*
    public function parseCommand($name) {
        if($isStar = (substr($name, -1) == '*')) {
            $name = substr($name, 0, -1);
        }

        $args = [];

        if(strlen($name) == 1) {
            $func = 'command_callSymbol';
            $args[] = $name;
        } else {
            $func = 'command_'.str_replace(['@'], ['AT'], ltrim($name, '\\'));
        }

        $args[] = $isStar;

        if(!method_exists($this, $func)) {
            throw new flex\latex\UnexpectedValueException(
                'Package '.$this->getName().' does not have a parser for command '.$name
            );
        }

        return call_user_func_array([$this, $func], $args);
    }

    public function parseEnvironment($name) {
        $func = 'environment_'.$name;

        if(!method_exists($this, $func)) {
            throw new flex\latex\UnexpectedValueException(
                'Package '.$this->getName().' does not have a parser for environment '.$name
            );
        }

        return call_user_func_array([$this, $func], []);
    }
    */
}