<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\halo;

class Http extends Base {
    
    const RUN_MODE = 'Http';
    
    protected $_responseAugmentor;
    
    
// Execute
    public function dispatch() {
        $this->_beginDispatch();
        
        $task = 'util build';
        $r = shell_exec('/mnt/dev/php/php-5.4.0.rc8/bin/php '.$this->getApplicationPath().'/entry/btc-pc.php '.$task);
        echo '<pre>'.$r.'</pre>';
        
        df\Launchpad::benchmark();
    }


    public function launchPayload($payload) {
        core\stub();
    }
    
// Environment
    public function getDebugTransport() {
        $output = parent::getDebugTransport();
        
        if($this->_responseAugmentor) {
            $output->setResponseAugmentor($this->_responseAugmentor);
        }
        
        return $output;
    }
    
    protected function _getNewDebugTransport() {
        return new core\debug\transport\Http();
    }
}
