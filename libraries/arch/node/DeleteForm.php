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

use DecodeLabs\Tagged\Html;

abstract class DeleteForm extends Form
{
    const ITEM_NAME = 'item';
    const IS_PERMANENT = true;
    const REQUIRE_CONFIRMATION = false;

    const DEFAULT_EVENT = 'delete';

    protected function getItemName()
    {
        return static::ITEM_NAME;
    }

    protected function requiresConfirmation(): bool
    {
        return static::REQUIRE_CONFIRMATION;
    }

    protected function createUi()
    {
        $itemName = $this->getItemName();
        $form = $this->content->addForm();
        $fs = $form->addFieldSet($this->_('%n% information', ['%n%' => ucfirst($itemName)]));

        $fs->push(Html::{'p'}($this->getMainMessage()));

        if (static::IS_PERMANENT) {
            $fs->push(
                $this->html->flashMessage('CAUTION: This action is permanent!', 'warning')
                    ->setDescription('This '.$itemName.' will be completely removed from the system and cannot be retrieved!')
            );
        }

        if (!$this->isValid()) {
            $fs->push($this->html->fieldError($this->values));
        }

        $this->createItemUi($fs);

        if ($this->requiresConfirmation()) {
            $this->createConfirmationUi($form);
        }


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


        $form->addButtonArea()->push(
            $mainButton, $cancelButton
        );
    }

    protected function getMainMessage()
    {
        return $this->html->_(
            'Are you sure you want to <strong>delete</strong> this %n%?',
            ['%n%' => $this->getItemName()]
        );
    }


    protected function createItemUi(/*aura\html\widget\IContainerWidget*/ $container)
    {
    }

    protected function createConfirmationUi($form)
    {
        $form->addFieldSet('Delete confirmation')->addField('Are you sure?')->push(
            $this->html->checkbox('confirm', $this->values->confirm, [
                    $this->html->icon('warning', 'I confirm I understand the consequences of DELETING this item, permanently, everywhere')
                    ->addClass('negative')
                ])
                ->isRequired(true)
        );
    }

    protected function customizeMainButton($button)
    {
    }
    protected function customizeCancelButton($button)
    {
    }


    protected function onDeleteEvent()
    {
        if ($this->requiresConfirmation()) {
            $val = $this->data->newValidator()
                ->addRequiredField('confirm', 'boolean')
                    ->setRequiredValue(true)

                ->validate($this->values);

            if (!$val->isValid()) {
                return;
            }
        }

        $output = $this->apply();

        if ($this->values->isValid()) {
            if ($message = $this->getFlashMessage()) {
                $this->comms->flash(
                    flex\Text::formatId($this->getItemName()).'.deleted',
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
        return $this->_(
            'The %n% has been successfully deleted',
            ['%n%' => $this->getItemName()]
        );
    }

    protected function finalize()
    {
        return $this->complete();
    }
}
