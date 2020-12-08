<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Node;

use df\arch\scaffold\IScaffold as Scaffold;

use DecodeLabs\Tagged\Html;

class DeleteSelectedForm extends AffectSelectedForm
{
    const IS_PERMANENT = true;
    const DEFAULT_EVENT = 'delete';

    public function __construct(Scaffold $scaffold)
    {
        $this->scaffold = $scaffold;
        parent::__construct($scaffold->getContext());
    }

    protected function requiresConfirmation(): bool
    {
        return $this->scaffold->recordDeleteRequiresConfirmation();
    }

    protected function renderUi($fs)
    {
        $fa = $fs->addField();
        $fa->push(Html::{'p'}($this->getMainMessage()));

        if (static::IS_PERMANENT) {
            $fa->push(
                $this->html->flashMessage('CAUTION: This action is permanent!', 'warning')
                    ->setDescription('These items will be completely removed from the system and cannot be retrieved!')
            );
        }

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
        $fs->addField('Are you sure?')->addClass('negative')->push(
            $this->html->checkbox('confirm', $this->values->confirm, [
                    $this->html->icon('warning'), 'I confirm I understand the consequences of DELETING these items, permanently, everywhere'
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
