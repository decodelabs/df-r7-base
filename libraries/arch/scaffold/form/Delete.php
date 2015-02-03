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

    public function __construct(arch\scaffold\IScaffold $scaffold) {
        $this->_scaffold = $scaffold;
        parent::__construct($scaffold->getContext());
    }

    protected function _getDataId() {
        return $this->_scaffold->getRecordId();
    }

    protected function _getItemName() {
        return $this->_scaffold->getRecordItemName();
    }

    protected function _renderItemDetails($container) {
        $container->push(
            $this->apex->component(ucfirst($this->_scaffold->getRecordKeyName()).'Details')
                ->setRecord($this->_scaffold->getRecord())
        );

        foreach($this->_scaffold->getRecordDeleteFlags() as $key => $label) {
            $container->push(
                $this->html->checkbox($key, $this->values->{$key}, $label)
            );
        }
    }

    protected function _deleteItem() {
        $flags = $this->_scaffold->getRecordDeleteFlags();
        $validator = $this->data->newValidator();

        foreach($flags as $key => $label) {
            $validator->addField($key, 'boolean');
        }

        $validator->validate($this->values);

        foreach($flags as $key => $label) {
            $flags[$key] = $validator[$key];
        }

        $this->_scaffold->deleteRecord($this->_scaffold->getRecord(), $flags);
    }
}