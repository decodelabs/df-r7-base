<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\node;

use df;
use df\core;

require_once dirname(__DIR__).'/_manifest.php';
require_once __DIR__.'/Group.php';

class Context extends Group {
    
    public $runningTime;
    
    protected $_isEnabled = true;
    protected $_transport;
    
    public function __construct() {
        $this->setNodeTitle('Context');
    }
    
    public function isEnabled() {
        return $this->_isEnabled;
    }
    
    public function enable() {
        $this->_isEnabled = true;
        return $this;
    }
    
    public function disable() {
        $this->_isEnabled = false;
        return $this;
    }
    
    public function addChild(core\debug\INode $node) {
        if(!$this->_isEnabled) {
            return $this;
        }
        
        return parent::addChild($node);
    }
    
    
// Transport
    public function setTransport(ITransport $transport) {
        $this->_transport = $transport;
        return $this;
    }
    
    public function getTransport() {
        if(!$this->_transport) {
            if(df\Launchpad::$application
            && $transport = df\Launchpad::$application->getDebugTransport()) {
                $this->setTransport($transport);
            } else {
                require_once __DIR__.'/transport/Base.php';
                $this->setTransport(new core\debug\transport\Base());
            }
        }
        
        return $this->_transport;
    }
    
    public function flush() {
        $this->runningTime = df\Launchpad::getRunningTime();
        $this->stackTrace(1);
        
        return $this->getTransport()->execute($this);
    }
}
