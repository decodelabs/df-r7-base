<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

df\Launchpad::loadBaseClass('core/debug/_manifest');
df\Launchpad::loadBaseClass('core/debug/StackCall');
df\Launchpad::loadBaseClass('core/debug/StackTrace');
df\Launchpad::loadBaseClass('core/log/_manifest');
df\Launchpad::loadBaseClass('core/log/node/Group');

class Context extends core\log\node\Group implements IContext {
    
    public $runningTime;
    
    protected $_transport;
    protected $_stackTrace;
    
    public function __construct() {
        $this->setNodeTitle('Context');
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
                df\Launchpad::loadBaseClass('core/debug/transport/Base');
                $this->setTransport(new core\debug\transport\Base());
            }
        }
        
        return $this->_transport;
    }
    
    public function flush() {
        $this->runningTime = df\Launchpad::getRunningTime();
        $this->_stackTrace = StackTrace::factory(1);
        $this->_stackTrace->stripDebugEntries();
        
        return $this->getTransport()->execute($this);
    }

    public function toString() {
        $renderer = new core\debug\renderer\PlainText($this);
        return $renderer->render();
    }
    
    public function getStackTrace() {
        return $this->_stackTrace;
    }
}
