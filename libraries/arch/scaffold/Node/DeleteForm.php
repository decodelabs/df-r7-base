<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Node;

use df\arch\node\DeleteForm as DeleteFormBase;
use df\arch\scaffold\IScaffold as Scaffold;

class DeleteForm extends DeleteFormBase
{
    protected $scaffold;

    public function __construct(Scaffold $scaffold)
    {
        $this->scaffold = $scaffold;
        parent::__construct($scaffold->getContext());
    }

    protected function getInstanceId()
    {
        return $this->scaffold->getRecordId();
    }

    protected function getItemName()
    {
        return $this->scaffold->getRecordItemName();
    }

    protected function requiresConfirmation(): bool
    {
        return $this->scaffold->recordDeleteRequiresConfirmation();
    }

    protected function createItemUi($container)
    {
        $container->push(
            $this->apex->component(ucfirst($this->scaffold->getRecordKeyName()).'Details')
                ->setRecord($this->scaffold->getRecord())
        );

        foreach ($this->scaffold->getRecordDeleteFlags() as $key => $label) {
            $container->addField()->addClass('stacked')->push(
                $this->html->checkbox($key, $this->values->{$key}, $label)
            );
        }
    }

    protected function apply()
    {
        $flags = $this->scaffold->getRecordDeleteFlags();
        $validator = $this->data->newValidator();

        foreach ($flags as $key => $label) {
            $validator->addField($key, 'boolean');
        }

        $validator->validate($this->values);

        foreach ($flags as $key => $label) {
            $flags[$key] = $validator[$key];
        }

        $this->scaffold->deleteRecord($this->scaffold->getRecord(), $flags);
    }

    protected function finalize()
    {
        return $this->complete(function () {
            //return $this->uri->directoryRequest($this->scaffold->getRecordParentUri($this->scaffold->getRecord()));
        });
    }
}
