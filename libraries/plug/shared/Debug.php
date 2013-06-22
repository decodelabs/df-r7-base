<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\shared;

use df;
use df\core;
use df\aura;

class Debug implements core\ISharedHelper {
    
    use core\TSharedHelper;
    
    public function dump($arg1) {
        core\debug()->addDumpList(func_get_args(), core\debug\StackCall::factory(1), false, false);
        return $this;
    }
    
    public function dumpDeep($arg1) {
        core\debug()->addDumpList(func_get_args(), core\debug\StackCall::factory(1), true, false);
        return $this;
    }
    
    public function dumpNow($arg1) {
        core\debug()->addDumpList(func_get_args(), core\debug\StackCall::factory(1), false, true)->render();
    }
    
    public function dumpDeepNow($arg1) {
        core\debug()->addDumpList(func_get_args(), core\debug\StackCall::factory(1), true, true)->render();
    }
    
    public function log($log) {
        core\debug()->log($log);
        return $this;
    }
    
    public function info($info) {
        core\debug()->info($info);
        return $this;
    }
    
    public function todo($todo) {
        core\debug()->todo($todo);
        return $this;
    }
    
    public function warning($warning) {
        core\debug()->warning($warning);
        return $this;
    }
    
    public function error($error) {
        core\debug()->error($error);
        return $this;
    }
    
    public function exception($exception) {
        core\debug()->exception($exception);
        return $this;
    }
    
    public function flush() {
        core\debug()->flush();
    }
}
