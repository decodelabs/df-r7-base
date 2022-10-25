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
use df\flex;

use DecodeLabs\Dictum;
use DecodeLabs\Tagged as Html;

abstract class ConfirmForm extends Form
{
    public const ITEM_NAME = 'item';
    public const DEFAULT_EVENT = 'confirm';
    public const DISPOSITION = 'positive';

    protected function getItemName()
    {
        return static::ITEM_NAME;
    }

    protected function createUi(): void
    {
        $itemName = $this->getItemName();
        $form = $this->content->addForm();
        $fs = $form->addFieldSet($this->_('%n% information', ['%n%' => ucfirst($itemName)]));

        $fs->push(Html::{'p'}($this->getMainMessage()));

        if (!$this->isValid()) {
            $fs->push($this->html->fieldError($this->values));
        }

        $this->createItemUi($fs);


        $mainButton = $this->html->eventButton(
                $this->eventName('confirm'),
                $this->_('Confirm')
            )
            ->setIcon('accept')
            ->setDisposition(static::DISPOSITION);

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

    protected function getMainMessage()
    {
        return $this->_('Are you sure?');
    }

    protected function createItemUi(/*aura\html\widget\IContainerWidget*/ $container)
    {
    }

    protected function customizeMainButton($button)
    {
    }
    protected function customizeCancelButton($button)
    {
    }


    protected function onConfirmEvent()
    {
        $output = $this->apply();

        if ($this->values->isValid()) {
            if ($message = $this->getFlashMessage()) {
                $this->comms->flash(
                    Dictum::id($this->getItemName()).'.confirm',
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

    abstract protected function apply();

    protected function getFlashMessage()
    {
        return $this->_('Action successfully completed');
    }

    protected function finalize()
    {
        return $this->complete();
    }
}
