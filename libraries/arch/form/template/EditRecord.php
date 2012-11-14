<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\template;

use df;
use df\core;
use df\arch;
use df\aura;
    
abstract class EditRecord extends arch\form\Action {

    use TTemplate;
    use TTemplate_Action;
    use TTemplate_ItemNameAware;
    use TTemplate_RecordAware;

    const DEFAULT_EVENT = 'save';

    const ITEM_NAME = null;
    const ENTITY_LOCATOR = null;

    protected function _preInit() {
        $this->_checkItemName();
        $this->_checkEntityLocator();
    }

    protected function _init() {
        $this->_initRecord();
    }

    protected function _loadRecord() {
        return $this->data->newRecord($this->_getEntityLocator());
    }

    protected function _onSaveEvent() {
        $this->_validateRecord();
        $this->_prepareRecord();

        if($this->isValid()) {
            $this->_saveRecord();
            $itemName = $this->_getItemName();

            $this->arch->notify(
                core\string\Manipulator::formatId($itemName).'.save',
                $this->_('The %n% has been successfully saved', ['%n%' => $itemName]),
                'success'
            );    

            return $this->_completeForm();
        }
    }

    protected function _validateRecord() {
        $validator = $this->data->newValidator();
        $this->_addValidatorFields($validator);
        $validator->validate($this->values)->applyTo($this->_record);
    }

    abstract protected function _addValidatorFields(core\validate\IHandler $validator);

    protected function _prepareRecord() {}

    protected function _saveRecord() {
        $this->_record->save();
    }
}