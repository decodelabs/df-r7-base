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

    public function __construct(arch\IContext $context, IScaffold $scaffold, Callable $callback, arch\IController $controller=null) {
        parent::__construct($context, $controller);

        $this->_callback = $callback;
        $this->_scaffold = $scaffold;
    }

    public function getCallback() {
        return $this->_callback;
    }

    public function getScaffold() {
        return $this->_scaffold;
    }

    public function execute() {
        $c = $this->_callback;
        return $c();
    }
}