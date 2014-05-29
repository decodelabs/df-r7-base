<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\component;

use df;
use df\core;
use df\arch;
use df\aura;

class Generic extends arch\component\Base {
    
    protected $_scaffold;
    protected $_name;

    public function __construct(arch\scaffold\IScaffold $scaffold, $name, array $args=null) {
        $this->_scaffold = $scaffold;
        $this->_name = $name;
        parent::__construct($scaffold->getContext(), $args);
    }

    protected function _execute() {
        $method = 'generate'.$this->_name.'Component';
        return call_user_func_array([$this->_scaffold, $method], $this->_componentArgs);
    }
}