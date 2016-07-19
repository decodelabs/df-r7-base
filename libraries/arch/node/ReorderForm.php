<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node;

use df;
use df\core;
use df\arch;
use df\aura;

abstract class ReorderForm extends Form {

    const ITEM_NAME = 'item';
    const PARENT_ITEM_NAME = 'parent';
    const DEFAULT_EVENT = 'reorder';

    const WEIGHT_FIELD = 'weight';

    protected function getItemName() {
        return static::ITEM_NAME;
    }

    protected function getParentItemName() {
        return static::PARENT_ITEM_NAME;
    }

    protected function getParentName() {
        return null;
    }


    protected function setDefaultValues() {
        $this->values->items = array_keys($this->fetchNameList());
    }

    abstract protected function fetchNameList();

    protected function createUi() {
        $form = $this->content->addForm();
        $itemName = $this->getItemName();
        $fs = $form->addFieldSet($this->_('Re-order %n% list', ['%n%' => $itemName]));

        if(null !== ($parentName = $this->getParentName())) {
            $fs->addField(ucfirst($this->getParentItemName()))->push(
                $this->html->textbox('parent', $parentName)
                    ->isDisabled(true)
            );
        }

        $fa = $fs->addField($this->_('%n% list', ['%n%' => ucfirst($itemName)]));

        $ids = $this->values->items->toArray();
        $nameList = $this->fetchNameList();
        $names = [];
        $count = count($nameList);

        foreach($ids as $key => $id) {
            if(!isset($nameList[$id])) {
                unset($this->values->items->{$key});
                continue;
            }

            $names[$id] = $nameList[$id];
            unset($nameList[$id]);
        }

        foreach($nameList as $id => $name) {
            $names[$id] = $name;
        }

        $i = 0;

        foreach($names as $id => $name) {
            $fa->push(
                $this->html('div.w-selection', [
                    $this->html->hidden('items[]', $id),

                    $this->html('div.body', $name)
                        ->setTitle($id),

                    $this->html->buttonArea(
                        $this->html->eventButton($this->eventName('up', $id), $this->_('Up'))
                            ->setIcon('arrow-up')
                            ->setDisposition('transitive')
                            ->isDisabled($i == 0),

                        $this->html->eventButton($this->eventName('down', $id), $this->_('Down'))
                            ->setIcon('arrow-down')
                            ->setDisposition('transitive')
                            ->isDisabled($i == $count - 1)
                    )
                ])
            );

            $i++;
        }

        $fs->addDefaultButtonGroup('reorder', $this->_('Re-order'), 'list');
    }

    protected function onUpEvent($id) {
        if(null === ($index = $this->_getIndex($id))) {
            return;
        }

        $this->values->items->move($index, -1);
        $this->values->items->clearKeys();
    }

    protected function onDownEvent($id) {
        if(null === ($index = $this->_getIndex($id))) {
            return;
        }

        $this->values->items->move($index, 1);
        $this->values->items->clearKeys();
    }

    protected function _getIndex($id) {
        foreach($this->values->items as $i => $testId) {
            if($id == $testId) {
                return $i;
            }
        }
    }

    protected function onReorderEvent() {
        $weights = array_flip($this->values->items->toArray());
        $output = $this->apply($weights);

        if($this->values->isValid()) {
            if($message = $this->getFlashMessage()) {
                $this->comms->flash(
                    $this->format->id($this->getItemName()).'.reorder',
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

    abstract protected function apply(array $weights);

    protected function getFlashMessage() {
        return $this->_(
            'The %n% list has been successfully re-ordered',
            ['%n%' => $this->getItemName()]
        );
    }

    protected function finalize() {
        return $this->complete();
    }
}