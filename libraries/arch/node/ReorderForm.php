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

use DecodeLabs\Dictum;
use DecodeLabs\Tagged as Html;

abstract class ReorderForm extends Form
{
    public const ITEM_NAME = 'item';
    public const PARENT_ITEM_NAME = 'parent';
    public const DEFAULT_EVENT = 'reorder';

    public const WEIGHT_FIELD = 'weight';

    protected function getItemName()
    {
        return static::ITEM_NAME;
    }

    protected function getParentItemName()
    {
        return static::PARENT_ITEM_NAME;
    }

    protected function getParentName()
    {
        return null;
    }


    protected function setDefaultValues(): void
    {
        $this->values->items = array_keys($this->fetchNameList());
    }

    abstract protected function fetchNameList();

    protected function createUi()
    {
        $form = $this->content->addForm();
        $itemName = $this->getItemName();
        $fs = $form->addFieldSet($this->_('Re-order %n% list', ['%n%' => $itemName]));

        if (null !== ($parentName = $this->getParentName())) {
            $fs->addField(ucfirst($this->getParentItemName()))->push(
                Html::{'strong'}($parentName)
            );
        }

        $fa = $fs->addField($this->_('%n% list', ['%n%' => ucfirst($itemName)]));

        $weights = $this->values->weights->toArray();
        $nameList = $this->fetchNameList();
        $names = [];
        $count = count($nameList);

        foreach ($weights as $id => $weight) {
            if (!isset($nameList[$id])) {
                unset($this->values->weights->{$id});
                continue;
            }

            $names[$id] = $nameList[$id];
            unset($nameList[$id]);
        }

        foreach ($nameList as $id => $name) {
            $names[$id] = $name;
        }

        $i = 0;

        foreach ($names as $id => $name) {
            $fa->push(
                Html::{'div.w.list.selection'}([
                    Html::{'div.body'}($name)
                        ->setTitle($id),

                    $this->html->buttonArea(
                        $this->html->textbox('weights['.$id.']', $i + 1)
                            ->setStyle('width', '4rem'),

                        $this->html->eventButton($this->eventName('up', (string)$id), $this->_('Up'))
                            ->setIcon('arrow-up')
                            ->setDisposition('transitive')
                            ->isDisabled($i == 0),

                        $this->html->eventButton($this->eventName('down', (string)$id), $this->_('Down'))
                            ->setIcon('arrow-down')
                            ->setDisposition('transitive')
                            ->isDisabled($i == $count - 1)
                    )
                ])
            );

            $i++;
        }

        $fs->addButtonArea(
            $this->html->saveEventButton('reorder', $this->_('Save'), 'list'),
            $this->html->eventButton('update', $this->_('Update'))
                ->setIcon('refresh')
                ->addClass('informative'),

            $this->html->buttonGroup(
                $this->html->resetEventButton(),
                $this->html->cancelEventButton()
            )
        );
    }

    protected function onUpEvent($id)
    {
        $this->values->weights->move($id, -1);
    }

    protected function onDownEvent($id)
    {
        $this->values->weights->move($id, 1);
    }

    protected function onUpdateEvent()
    {
        $weights = $this->values->weights->toArray();

        uasort($weights, function ($a, $b) {
            return $a <=> $b;
        });

        $weights = array_flip(array_keys($weights));

        array_walk($weights, function (&$value) {
            $value = $value + 1;
        });

        $this->values->weights = $weights;
    }

    protected function onReorderEvent()
    {
        $this->onUpdateEvent();

        $weights = $this->values->weights->toArray();
        $output = $this->apply($weights);

        if ($this->values->isValid()) {
            if ($message = $this->getFlashMessage()) {
                $this->comms->flash(
                    Dictum::id($this->getItemName()).'.reorder',
                    $message,
                    'success'
                );
            }

            $complete = $this->finalize();

            if ($output !== null) {
                return $output;
            } else {
                return $complete;
            }
        }
    }

    abstract protected function apply(array $weights);

    protected function getFlashMessage()
    {
        return $this->_(
            'The %n% list has been successfully re-ordered',
            ['%n%' => $this->getItemName()]
        );
    }

    protected function finalize()
    {
        return $this->complete();
    }
}
