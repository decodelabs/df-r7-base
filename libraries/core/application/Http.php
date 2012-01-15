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
    protected $_debugTransport;
    
    
// Execute
    public function dispatch() {
        $this->_beginDispatch();

        
        require_once df\Launchpad::ROOT_PATH.'/base/tests/core/collection/SetBatch.php';
        
        $batch = new \df\test\core\collection\SetBatch();
        $batch->run();
        
        df\Launchpad::benchmark();
    }


    public function launchPayload($payload) {
        core\stub();
    }
    
// Environment
    public function getDebugTransport() {
        if(!$this->_debugTransport) {
            $this->_debugTransport = new core\debug\transport\Http();
        }
        
        if($this->_responseAugmentor) {
            $this->_debugTransport->setResponseAugmentor($this->_responseAugmentor);
        }
        
        return $this->_debugTransport;
    }
}
