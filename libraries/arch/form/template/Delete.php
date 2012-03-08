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

class Delete extends arch\form\Action {
    
    const ITEM_NAME = 'item';
    const IS_PERMANENT = true;
    
    const DEFAULT_EVENT = 'delete';
    
    protected function _getItemName() {
        return static::ITEM_NAME;
    }
    
    protected function _createUi() {
        $itemName = $this->_getItemName();
        $form = $this->content->addForm();
        $fs = $form->addFieldSet($this->_('%n% information', array('%n%' => ucfirst($itemName))));
        
        $fs->push($this->html->string(
            '<p>'.$this->_(
                'Are you sure you want to delete this %n%?',
                array('%n%' => $itemName)
            ).'</p>'
        ));
        
        /*
        if(static::IS_PERMANENT) {
            $fs->push(
                $this->html->notification(
                    $this->_('CAUTION: This action is permanent!'), 'warning'
                )
            );
        }
        */
        
        $this->_renderMessages($fs);
        $this->_renderItemDetails($fs);
        
        $fs->addButtonArea()->push(
            $this->html->eventButton(
                    $this->eventNAme('delete'),
                    $this->_('Delete')
                ),
                
            $this->html->eventButton(
                    $this->eventName('cancel'),
                    $this->_('Cancel')
                )
        );
    }
    
    
    protected function _renderMessages(aura\html\widget\IContainerWidget $container) {}
    protected function _renderItemDetails(aura\html\widget\IContainerWidget $container) {}
    
    
    protected function _onDeleteEvent() {
        $this->_validateItem();
        
        if($this->values->isValid()) {
            $this->_deleteItem();
            
            // TODO: notification
            
            return $this->complete();
        }
    }
    
    protected function _validateItem() {}
    protected function _deleteItem() {}
}