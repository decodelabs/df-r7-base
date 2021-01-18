<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Node;

use df\arch\Scaffold;

use DecodeLabs\Tagged\Html;

class DeleteSelectedForm extends AffectSelectedForm
{
    const DEFAULT_EVENT = 'delete';

    public function __construct(Scaffold $scaffold)
    {
        $this->scaffold = $scaffold;
        parent::__construct($scaffold->getContext());
    }

    protected function isPermanent(): bool
    {
        return $this->scaffold->areRecordDeletesPermanent();
    }

    protected function isShared(): bool
    {
        return $this->scaffold->areRecordsShared();
    }

    protected function isParent(): bool
    {
        return $this->scaffold->areRecordsParents();
    }

    protected function requiresConfirmation(): bool
    {
        return $this->scaffold->recordDeleteRequiresConfirmation();
    }

    protected function renderHeader($form)
    {
        $form->push(Html::{'p'}($this->getMainMessage()));

        if ($this->isPermanent()) {
            $form->push(
                $this->html->flashMessage('CAUTION: This action is permanent!', 'warning')
                    ->setDescription('These items will be completely removed from the system and cannot be retrieved!')
            );
        }

        if ($this->isParent()) {
            $form->push(
                $this->html->flashMessage('NOTE: These items may contains child records', 'info')
                    ->setDescription('Some or all child records that are contained in this these items will also be deleted!')
            );
        }

        if ($this->isShared()) {
            $form->push(
                $this->html->flashMessage('WARNING: These items may be shared with other items', 'error')
                    ->setDescription('Deleting these items will remove them from all other related records that depend on them!')
            );
        }
    }

    protected function renderUi($fs)
    {
        if ($this->requiresConfirmation()) {
            $this->createConfirmationUi($fs);
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


        $fs->addButtonArea()->addClass('')->push(
            $mainButton,
            $cancelButton
        );
    }

    protected function getMainMessage()
    {
        return $this->html->_(
            'Are you sure you want to <strong>delete</strong> these items?'
        );
    }

    protected function createConfirmationUi($fs)
    {
        $fs->addField()->addClass(
            $this->isShared() || $this->isParent() ?
                'negative' : 'warning'
        )->addStyles([
            'font-size' => $this->isShared() ? '1.4em' : '1.2em'
        ])->push(
            $this->html->checkbox('confirm', $this->values->confirm, [
                    $this->html->icon('warning'), function () {
                        yield 'I confirm I understand the consequences of ';
                        yield Html::strong('DELETING');
                        yield ' these ';

                        if ($this->isShared()) {
                            yield Html::{'strong > em'}('shared');
                            yield ' ';
                        }

                        yield 'items';

                        if ($this->isParent()) {
                            yield ' and their ';
                            yield Html::em('children');
                        }

                        if ($this->isPermanent()) {
                            yield ', ';
                            yield Html::strong('permanently');
                        }

                        if ($this->isShared()) {
                            yield ', ';
                            yield Html::{'strong > em'}('everywhere');
                        }
                    }
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
                    'selected.deleted',
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

    protected function apply()
    {
        foreach ($this->fetchSelectedRecords() as $item) {
            if ($this->scaffold->canDeleteRecord($item)) {
                $item->delete();
            }
        }
    }

    protected function getFlashMessage()
    {
        return $this->_(
            'The items have been successfully deleted'
        );
    }

    protected function finalize()
    {
        return $this->complete();
    }
}
