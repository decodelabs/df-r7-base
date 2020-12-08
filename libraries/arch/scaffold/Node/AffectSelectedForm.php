<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Node;

use df\arch\node\Form as Form;

abstract class AffectSelectedForm extends Form
{
    const AUTO_INSTANCE_ID_IGNORE = ['selected'];

    protected $scaffold;
    protected $ids = [];

    protected function init()
    {
        if (!$this->scaffold) {
            $this->scaffold = $this->scaffold;
        }

        if (isset($this->request['selected'])) {
            $this->ids = explode(',', $this->request['selected']);
        }
    }

    protected function getInstanceId()
    {
        $output = parent::getInstanceId();
        $hash = md5($this->request['selected']);

        if ($output === null) {
            $output = $hash;
        } else {
            $output .= '|'.$hash;
        }

        return $output;
    }

    protected function createUi()
    {
        $form = $this->content->addForm();

        $form->push(function () {
            $keyName = $this->scaffold->getRecordKeyName();
            $query = $this->scaffold->queryRecordList($this->request->getNode())
                ->where($this->scaffold->getRecordIdField(), 'in', $this->ids)
                ->limit(100)
                ->paginateWith($this->request->query);

            return $this->apex->component(ucfirst($keyName).'List', ['actions' => false])
                ->setCollection($query)
                ->setSlot('scaffold', $this->scaffold)
                ->render();
        });

        $fs = $form->addFieldSet($this->_('With selected...'));
        $this->renderUi($fs);
    }

    protected function renderUi($fs)
    {
    }

    protected function fetchSelectedRecords()
    {
        return $this->scaffold->getRecordAdapter()->fetch()
            ->where('id', 'in', $this->ids);
    }
}
