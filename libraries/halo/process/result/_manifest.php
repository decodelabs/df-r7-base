<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process\result;

use df;
use df\core;
use df\halo;

// Exceptions
interface IException {}


// Interfaces
interface IResult {
    
}


interface IBlockingResult extends IResult {
    public function registerFailure();
    public function hasLaunched();
    
    public function registerCompletion();
    public function hasCompleted();
    
    public function getTimer();
    
    public function setOutput($output);
    public function appendOutput($output);
    public function hasOutput();
    public function getOutput();
    
    
    public function setError($error);
    public function appendError($error);
    public function hasError();
    public function getError();
}


interface IBackgroundResult extends IResult {
    
}


interface IManagedResult extends IResult {
    
}