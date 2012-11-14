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
    
trait TTemplate {

    protected function _createUi() {
        $this->content->push($this->html->notification($this->_(
            'This is the default template form ui - you should implement your own!'
        ), 'debug'));
    }
}


trait TTemplate_Action {

    protected function _completeForm() {
        return $this->complete();
    }
}


trait TTemplate_ItemNameAware {

    protected function _checkItemName() {
        if(static::ITEM_NAME === null) {
            throw new arch\form\LogicException(
                'Item name has not been defined'
            );
        }
    }

    protected function _getItemName() {
        return static::ITEM_NAME;
    }
}


trait TTemplate_RecordAware {

    protected $_record;

    protected function _initRecord() {
        if(!$this->_record = $this->_loadRecord()) {
            if(method_exists($this, '_getItemName')) {
                $name = $this->_getItemName();
            } else {
                $name = 'item';
            }

            $this->throwError(404, 'The selected '.$name.' could not be loaded');
        }
    }

    protected function _fetchRecordForAction($id, $action=null) {
        return $this->data->fetchForAction(
            $this->_getEntityLocator(),
            $id, $action
        );
    }

    protected function _getDataId() {
        if(!$this->_record) {
            throw new arch\form\LogicException(
                'No record has been fetched for manipulation'
            );
        }

        return $this->_record['id'];
    }

    protected function _checkEntityLocator() {
        if(static::ENTITY_LOCATOR === null) {
            throw new arch\form\LogicException(
                'Item entity locator has not been defined'
            );
        }
    }

    protected function _getEntityLocator() {
        return static::ENTITY_LOCATOR;
    }
}
