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

class HeaderBar extends arch\component\template\HeaderBar {
    
    protected $_scaffold;
    protected $_name;

    public function __construct(arch\scaffold\IScaffold $scaffold, $name, array $args=null) {
        $this->_scaffold = $scaffold;
        $this->_name = ucfirst($name);
        $this->_icon = $scaffold->getDirectoryIcon();

        parent::__construct($scaffold->getContext(), $args);
    }

    protected function _addOperativeLinks($menu) {
        $method = 'add'.$this->_name.'HeaderBarOperativeLinks';

        if(method_exists($this->_scaffold, $method)) {
            $this->_scaffold->{$method}($menu, $this);
        }
    }

    protected function _addSubOperativeLinks($menu) {
        $method = 'add'.$this->_name.'HeaderBarSubOperativeLinks';

        if(method_exists($this->_scaffold, $method)) {
            $this->_scaffold->{$method}($menu, $this);
        }
    }

    protected function _addTransitiveLinks($menu) {
        $method = 'add'.$this->_name.'HeaderBarTransitiveLinks';

        if(method_exists($this->_scaffold, $method)) {
            $this->_scaffold->{$method}($menu, $this);
        }
    }

    protected function _addSectionLinks($menu) {
        $method = 'add'.$this->_name.'HeaderBarSectionLinks';

        if(method_exists($this->_scaffold, $method)) {
            $this->_scaffold->{$method}($menu, $this);
        }
    }
}