<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cli;

use df;
use df\core;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface ICommand extends core\collection\IQueue, core\collection\IRandomAccessCollection, core\IStringProvider {
    public function setExecutable($executable);
    public function getExecutable();
    
    public function addArgument($argument);
    public function getArguments();
}


interface IArgument extends core\IStringProvider {
    public function getOption();
    public function isOption();
    public function isLongOption();
    public function isShortOption();
    public function isOptionCluster();
    public function getClusterOptions();
    
    public function getValue();
    public function hasValue();
}

