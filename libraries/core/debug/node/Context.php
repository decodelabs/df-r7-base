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

class Context extends Group implements core\debug\IContext {
    
    public $runningTime;
    
    protected $_isEnabled = true;
    protected $_transport;
    protected $_stackTrace;
    
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
    public function setTransport(core\debug\ITransport $transport) {
        $this->_transport = $transport;
        return $this;
    }
    
    public function getTransport() {
        if(!$this->_transport) {
            if(df\Launchpad::$application
            && $transport = df\Launchpad::$application->getDebugTransport()) {
                $this->setTransport($transport);
            } else {
                require_once dirname(__DIR__).'/transport/Base.php';
                $this->setTransport(new core\debug\transport\Base());
            }
        }
        
        return $this->_transport;
    }
    
    public function flush() {
        $this->runningTime = df\Launchpad::getRunningTime();
        $this->_stackTrace = core\debug\StackTrace::factory(1);
        $this->_stackTrace->stripDebugEntries();
        
        return $this->getTransport()->execute($this);
    }
    
    public function getStackTrace() {
        return $this->_stackTrace;
    }
    
    
// Nodes
    public function getNodeCounts() {
        $output = [
            'info' => 0,
            'todo' => 0,
            'warning' => 0,
            'error' => 0,
            'deprecated' => 0,
            'stub' => 0,
            'dump' => 0,
            'exception' => 0,
            'stackTrace' => 0,
            'group' => 0
        ];
        
        $this->_countNodes($this, $output);
        return $output;
    }
    
    private function _countNodes(core\debug\IGroupNode $node, &$counts) {
        foreach($node->getChildren() as $child) {
            $counts[$child->getNodeType()]++;
            
            if($child instanceof core\debug\IGroupNode) {
                $this->_countNodes($child, $counts);
            }
        }
    }
}
