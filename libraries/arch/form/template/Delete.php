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
        $fs = $form->addFieldSet($this->_('%n% information', ['%n%' => ucfirst($itemName)]));
        
        $fs->push($this->html(
            '<p>'.$this->_getMainMessage($itemName).'</p>'
        ));
        
        if(static::IS_PERMANENT) {
            $fs->push(
                $this->html->flashMessage(
                    $this->_('CAUTION: This action is permanent!'), 'warning'
                )
            );
        }

        $this->_renderMessages($fs);

        if(!$this->isValid()) {
            $fs->push($this->html->fieldError($this->values));   
        }

        $this->_renderItemDetails($fs);

        $fs->addButtonArea()->push(
            $this->html->eventButton(
                    $this->eventName('delete'),
                    $this->_getMainButtonText()
                )
                ->setIcon($this->_getMainButtonIcon()),
                
            $this->html->eventButton(
                    $this->eventName('cancel'),
                    $this->_('Cancel')
                )
                ->setIcon('cancel')
        );
    }
    
    protected function _getMainMessage($itemName) {
        return $this->_(
            'Are you sure you want to delete this %n%?',
            ['%n%' => $itemName]
        );
    }

    protected function _getMainButtonText() {
        return $this->_('Delete');
    }

    protected function _getMainButtonIcon() {
        return 'delete';
    }
    
    protected function _renderMessages(/*aura\html\widget\IContainerWidget*/ $container) {}
    protected function _renderItemDetails(/*aura\html\widget\IContainerWidget*/ $container) {}
    
    
    protected function _onDeleteEvent() {
        $this->_validateItem();
        
        if($this->values->isValid()) {
            $this->_deleteItem();
            $itemName = $this->_getItemName();
            
            $this->comms->flash(
                core\string\Manipulator::formatId($itemName).'.deleted', 
                $this->_(
                    'The %n% has been successfully deleted', 
                    ['%n%' => $itemName]
                ), 
                'success'
            );
            
            return $this->_completeForm();
        }
    }
    
    protected function _validateItem() {}
    protected function _deleteItem() {}

    protected function _completeForm() {
        return $this->complete();
    }
}