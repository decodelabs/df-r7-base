<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\form;

use df;
use df\core;
use df\arch;
use df\aura;

class Delete extends arch\form\template\Delete {
    
    protected $_scaffold;

    public function __construct(arch\scaffold\IScaffold $scaffold, arch\IController $controller=null) {
        $this->_scaffold = $scaffold;
        parent::__construct($scaffold->getContext(), $controller);
    }

    protected function _getItemName() {
        return $this->_scaffold->getRecordItemName();
    }

    protected function _renderItemDetails($container) {
        $container->push(
            $this->import->component(ucfirst($this->_scaffold->getRecordKeyName()).'Details', $this->_context->location)
                ->setRecord($this->_scaffold->getRecord())
        );
    }

    protected function _deleteItem() {
        $this->_scaffold->getRecord()->delete();
    }
}