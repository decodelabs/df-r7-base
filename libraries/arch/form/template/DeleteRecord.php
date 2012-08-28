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
    
abstract class DeleteRecord extends Delete {

    use TTemplate_RecordAware;

    protected function _init() {
    	$this->_checkItemName();
    	$this->_checkEntityLocator();
    	$this->_initRecord();
    }

    protected function _renderItemDetails(aura\html\widget\IContainerWidget $container) {
    	$container->push(
    		$attributeList = $this->html->attributeList($this->_record)
		);

		$this->_addAttributeListFields($attributeList);
    }

    abstract protected function _addAttributeListFields($attributeList);

    protected function _deleteItem() {
    	return $this->_deleteRecord();
    }

    protected function _deleteRecord() {
    	$this->_record->delete();
    }
}