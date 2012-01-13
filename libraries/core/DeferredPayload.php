<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core;

use df;
use df\core;

class DeferredPayload implements IDeferredPayload {
    
    protected $_payload;
    protected $_application;
    
    public function __construct(IApplication $application, IPayload $payload) {
        $this->_application = $application;
        $this->_payload = $payload;
    }
    
    public function getApplication() {
        return $this->_application;
    }
    
    public function getPayload() {
        if($this->_payload instanceof IDeferredPayload) {
            return $this->_payload->getPayload();
        }
        
        return $this->_payload;
    }
}
