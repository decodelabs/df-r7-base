<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\view;

use df;
use df\core;
use df\aura;

class Debug implements aura\view\IHelper {
    
    protected $_view;
    
    public function __construct(aura\view\IView $view) {
        $this->_view = $view;
    }
    
    public function dump($arg1) {
        core\debug()->dumpArgs(func_get_args());
        return $this;
    }
    
    public function dumpDeep($arg1) {
        core\debug()->dumpArgs(func_get_args(), true);
        return $this;
    }
    
    public function dumpNow($arg1) {
        core\debug()->dumpArgs(func_get_args())->flush();
    }
    
    public function dumpDeepNow($arg1) {
        core\debug()->dumpArgs(func_get_args(), true)->flush();
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
