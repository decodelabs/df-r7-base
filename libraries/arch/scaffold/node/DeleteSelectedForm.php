<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\node;

use df;
use df\core;
use df\arch;
use df\aura;

class DeleteSelectedForm extends AffectSelectedForm
{
    const IS_PERMANENT = true;

    const DEFAULT_EVENT = 'delete';

    protected function renderUi($fs)
    {
        $fs->push($this->html('p', $this->getMainMessage()));

        if (static::IS_PERMANENT) {
            $fs->push(
                $this->html->flashMessage(
                    $this->_('CAUTION: This action is permanent!'),
                    'warning'
                )
            );
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
        return $this->_(
            'Are you sure you want to delete these items?'
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
        $items = $this->_scaffold->getRecordAdapter()->fetch()
            ->where('id', 'in', $this->_ids);

        foreach ($items as $item) {
            if ($this->_scaffold->canDeleteRecord($item)) {
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
