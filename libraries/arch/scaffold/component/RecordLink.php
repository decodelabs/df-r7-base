<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\component;

use df;
use df\core;
use df\arch;
use df\aura;

use df\arch\scaffold\Record\DataProvider as RecordDataProviderScaffold;

use DecodeLabs\Tagged\Html;

class RecordLink extends arch\component\RecordLink
{
    protected $_scaffold;

    public function __construct(RecordDataProviderScaffold $scaffold, array $args=null)
    {
        $this->_scaffold = $scaffold;
        parent::__construct($scaffold->getContext(), $args);

        if ($this->_record !== null) {
            $this->_icon = $scaffold->getRecordIcon($this->_record);
        } else {
            $this->_icon = $scaffold->getDirectoryIcon();
        }
    }

    protected function _getRecordId()
    {
        return $this->_scaffold->getRecordId($this->_record);
    }

    protected function _getRecordName()
    {
        $output = $this->_scaffold->getRecordName($this->_record);

        if ($this->_scaffold->getRecordNameField() == 'slug') {
            $output = Html::{'samp'}($output);
        }

        return $output;
    }

    protected function _getRecordUrl($id)
    {
        return $this->_scaffold->getRecordUrl($this->_record);
    }

    protected function _decorateBody($body)
    {
        return $body;
    }

    protected function _decorate($link)
    {
        return $this->_scaffold->decorateRecordLink($link, $this);
    }
}
