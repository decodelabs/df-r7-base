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

    protected $_subOperativeLinkBuilder;

    public function __construct(arch\scaffold\IScaffold $scaffold, $name, array $args=null) {
        $this->_scaffold = $scaffold;
        $this->_name = ucfirst($name);
        $this->_icon = $scaffold->getDirectoryIcon();

        parent::__construct($scaffold->getContext(), $args);
    }

    protected function _addOperativeLinks($menu) {
        $method = 'add'.$this->_name.'OperativeLinks';

        if(method_exists($this->_scaffold, $method)) {
            $this->_scaffold->{$method}($menu, $this);
        }
    }

    protected function _addSubOperativeLinks($menu) {
        $method = 'add'.$this->_name.'SubOperativeLinks';

        if(method_exists($this->_scaffold, $method)) {
            $this->_scaffold->{$method}($menu, $this);
        }

        if($this->_subOperativeLinkBuilder) {
            call_user_func($this->_subOperativeLinkBuilder, $menu, $this->_scaffold->view, $this->_scaffold);
        }
    }

    protected function _addTransitiveLinks($menu) {
        $method = 'add'.$this->_name.'TransitiveLinks';

        if(method_exists($this->_scaffold, $method)) {
            $this->_scaffold->{$method}($menu, $this);
        }
    }

    protected function _addSectionLinks($menu) {
        $method = 'add'.$this->_name.'SectionLinks';

        if(method_exists($this->_scaffold, $method)) {
            $this->_scaffold->{$method}($menu, $this);
        }
    }

    public function setSubOperativeLinkBuilder(Callable $builder=null) {
        $this->_subOperativeLinkBuilder = $builder;
        return $this;
    }

    public function getSubOperativeLinkBuilder() {
        return $this->_subOperativeLinkBuilder;
    }
}