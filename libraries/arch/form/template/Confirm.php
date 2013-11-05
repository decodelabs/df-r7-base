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

abstract class Confirm extends arch\form\Action {
    
    const ITEM_NAME = 'item';
    const DEFAULT_EVENT = 'confirm';
    const DISPOSITION = 'positive';
    
    protected function _getItemName() {
        return static::ITEM_NAME;
    }

    protected function _createUi() {
        $itemName = $this->_getItemName();
        $form = $this->content->addForm();
        $fs = $form->addFieldSet($this->_('%n% information', ['%n%' => ucfirst($itemName)]));
        
        $fs->push($this->html->string(
            '<p>'.$this->_getMainMessage($itemName).'</p>'
        ));
        
        $this->_renderMessages($fs);
        
        if(!$this->isValid()) {
            $fs->push($this->html->fieldError($this->values));   
        }

        $this->_renderItemDetails($fs);
        
        $fs->addButtonArea()->push(
            $this->html->eventButton(
                    $this->eventName('confirm'),
                    $this->_getMainButtonText()
                )
                ->setIcon($this->_getMainButtonIcon())
                ->setDisposition(static::DISPOSITION),
                
            $this->html->eventButton(
                    $this->eventName('cancel'),
                    $this->_('Cancel')
                )
                ->setIcon('cancel')
        );
    }
    
    protected function _getMainButtonText() {
        return $this->_('Confirm');
    }

    protected function _getMainButtonIcon() {
        return 'accept';
    }
    
    protected function _renderMessages(/*aura\html\widget\IContainerWidget*/ $container) {}
    protected function _renderItemDetails(/*aura\html\widget\IContainerWidget*/ $container) {}
    
    
    protected function _onConfirmEvent() {
        $this->_validateItem();
        
        if($this->values->isValid()) {
            $output = $this->_apply();
            
            $this->comms->flash(
                core\string\Manipulator::formatId($this->_getItemName()).'.confirm', 
                $this->_getFlashMessage(), 
                'success'
            );
            
            $complete = $this->_completeForm();

            if($output !== null) {
                return $output;
            } else {
                return $complete;
            }
        }
    }

    protected function _getMainMessage($itemName) {
        return $this->_('Are you sure?');
    }

    protected function _getFlashMessage() {
        return $this->_('Action successfully completed');
    }
    
    protected function _validateItem() {}
    abstract protected function _apply();

    protected function _completeForm() {
        return $this->complete();
    }
}