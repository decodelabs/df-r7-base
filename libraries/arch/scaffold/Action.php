<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df;
use df\core;
use df\arch;
use df\aura;

class Action extends arch\Action {
    
    protected $_callback;
    protected $_scaffold;

    public function __construct(arch\IContext $context, IScaffold $scaffold, $callback, arch\IController $controller=null) {
        parent::__construct($context, $controller);

        $this->_callback = core\lang\Callback::factory($callback);
        $this->_scaffold = $scaffold;
    }

    public function getCallback() {
        return $this->_callback;
    }

    public function getScaffold() {
        return $this->_scaffold;
    }

    public function execute() {
        if(null !== ($pre = $this->_scaffold->onActionDispatch($this))) {
            return $pre;
        }

        return $this->_callback->invoke();
    }

    protected function _getClassDefaultAccess() {
        return $this->_scaffold->getDefaultAccess();
    }
}