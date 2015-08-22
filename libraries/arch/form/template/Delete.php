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

abstract class Delete extends arch\form\Action {
    
    const ITEM_NAME = 'item';
    const IS_PERMANENT = true;
    
    const DEFAULT_EVENT = 'delete';
    
    protected function getItemName() {
        return static::ITEM_NAME;
    }

    protected function createUi() {
        $itemName = $this->getItemName();
        $form = $this->content->addForm();
        $fs = $form->addFieldSet($this->_('%n% information', ['%n%' => ucfirst($itemName)]));
        
        $fs->push($this->html('p', $this->getMainMessage()));
        
        if(static::IS_PERMANENT) {
            $fs->push(
                $this->html->flashMessage(
                    $this->_('CAUTION: This action is permanent!'), 'warning'
                )
            );
        }

        if(!$this->isValid()) {
            $fs->push($this->html->fieldError($this->values));   
        }

        $this->createItemUi($fs);


        $mainButton = $this->html->eventButton(
                $this->eventName('delete'),
                $this->_('Delete')
            )
            ->setIcon('delete');

        $cancelButton = $this->html->eventButton(
                $this->eventName('cancel'),
                $this->_('Cancel')
            )
            ->setIcon('cancel');

        $this->customizeMainButton($mainButton);
        $this->customizeCancelButton($cancelButton);


        $fs->addButtonArea()->push(
            $mainButton, $cancelButton
        );
    }
    
    protected function getMainMessage() {
        return $this->_(
            'Are you sure you want to delete this %n%?',
            ['%n%' => $this->getItemName()]
        );
    }


    protected function createItemUi(/*aura\html\widget\IContainerWidget*/ $container) {}

    protected function customizeMainButton($button) {}
    protected function customizeCancelButton($button) {}
    
    
    protected function onDeleteEvent() {
        $output = $this->apply();
        
        if($this->values->isValid()) {
            if($message = $this->getFlashMessage()) {
                $this->comms->flash(
                    core\string\Manipulator::formatId($this->getItemName()).'.deleted', 
                    $message, 
                    'success'
                );
            }
            
            $complete = $this->finalize();

            if($output !== null) {
                return $output;
            } else {
                return $complete;
            }
        }
    }

    abstract protected function apply();

    protected function getFlashMessage() {
        return $this->_(
            'The %n% has been successfully deleted', 
            ['%n%' => $this->getItemName()]
        );
    }

    protected function finalize() {
        return $this->complete();
    }
}