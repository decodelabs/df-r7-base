<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node;

use DecodeLabs\Dictum;

use DecodeLabs\Tagged as Html;
use df\aura;

abstract class DeleteForm extends Form
{
    public const ITEM_NAME = 'item';
    public const IS_PERMANENT = true;
    public const IS_SHARED = false;
    public const IS_PARENT = false;
    public const REQUIRE_CONFIRMATION = false;

    public const DEFAULT_EVENT = 'delete';

    protected function getItemName()
    {
        return static::ITEM_NAME;
    }

    protected function isPermanent(): bool
    {
        return static::IS_PERMANENT;
    }

    protected function isShared(): bool
    {
        return static::IS_SHARED;
    }

    protected function isParent(): bool
    {
        return static::IS_PARENT;
    }

    protected function requiresConfirmation(): bool
    {
        return static::REQUIRE_CONFIRMATION;
    }

    protected function createUi(): void
    {
        $itemName = $this->getItemName();
        $form = $this->content->addForm();
        $fs = $form->addFieldSet($this->_('%n% information', ['%n%' => ucfirst($itemName)]));

        $fs->push(Html::{'p'}($this->getMainMessage()));

        if ($this->isPermanent()) {
            $fs->push(
                $this->html->flashMessage('CAUTION: This action is permanent!', 'warning')
                    ->setDescription('This ' . $itemName . ' will be completely removed from the system and cannot be retrieved!')
            );
        }

        if ($this->isParent()) {
            $fs->push(
                $this->html->flashMessage('NOTE: This ' . $itemName . ' may contain child items', 'info')
                    ->setDescription('Some or all child records that are contained in this ' . $itemName . ' will also be deleted!')
            );
        }

        if ($this->isShared()) {
            $fs->push(
                $this->html->flashMessage('WARNING: This ' . $itemName . ' may be shared with other items', 'error')
                    ->setDescription('Deleting this ' . $itemName . ' will remove it from all other related records that depend on it')
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
            $mainButton,
            $cancelButton
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
        $form->addFieldSet('Delete confirmation')->addField()->addClass(
            $this->isShared() || $this->isParent() ?
                'negative' : 'warning'
        )->addStyles([
            'font-size' => $this->isShared() ? '1.3em' : '1.15em'
        ])->push(
            $this->html->checkbox('confirm', $this->values->confirm, [
                    $this->html->icon('warning', function () {
                        yield 'I confirm I understand the consequences of ';
                        yield Html::strong('DELETING');
                        yield ' this ';

                        if ($this->isShared()) {
                            yield Html::{'strong > em'}('shared');
                            yield ' ';
                        }

                        yield $this->getItemName();

                        if ($this->isParent()) {
                            yield ' and its ';
                            yield Html::em('children');
                        }

                        if ($this->isPermanent()) {
                            yield ', ';
                            yield Html::strong('permanently');
                        }

                        if ($this->isShared()) {
                            yield ', ';
                            yield Html::{'strong > em'}('EVERYWHERE');
                        }
                    })
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
                    Dictum::id($this->getItemName()) . '.deleted',
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
