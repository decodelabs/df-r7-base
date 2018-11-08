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

abstract class AffectSelectedForm extends arch\node\Form
{
    protected $_scaffold;
    protected $_ids = [];

    public function __construct(arch\scaffold\IScaffold $scaffold)
    {
        $this->_scaffold = $scaffold;
        parent::__construct($scaffold->getContext());
    }

    protected function init()
    {
        if (isset($this->request['selected'])) {
            $this->_ids = explode(',', $this->request['selected']);
        }
    }

    protected function createUi()
    {
        $form = $this->content->addForm();

        $form->push(function () {
            $keyName = $this->_scaffold->getRecordKeyName();
            $query = $this->_scaffold->queryRecordList($this->request->getNode())
                ->where($this->_scaffold->getRecordIdField(), 'in', $this->_ids)
                ->limit(100)
                ->paginateWith($this->request->query);

            return $this->apex->component(ucfirst($keyName).'List', ['actions' => false])
                ->setCollection($query)
                ->setSlot('scaffold', $this->_scaffold)
                ->render();
        });

        $fs = $form->addFieldSet($this->_('With selected...'));
        $this->renderUi($fs);
    }

    protected function renderUi($fs)
    {
    }
}
