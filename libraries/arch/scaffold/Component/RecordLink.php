<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Component;

use df;
use df\core;
use df\arch;
use df\aura;

use df\arch\component\RecordLink as RecordLinkBase;
use df\arch\scaffold\Record\DataProvider as RecordDataProviderScaffold;

use DecodeLabs\Tagged\Html;

class RecordLink extends RecordLinkBase
{
    protected $scaffold;

    public function __construct(RecordDataProviderScaffold $scaffold, array $args=null)
    {
        $this->scaffold = $scaffold;
        parent::__construct($scaffold->getContext(), $args);

        if ($this->record !== null) {
            $this->icon = $scaffold->iconifyRecord($this->record);
        } else {
            $this->icon = $scaffold->getDirectoryIcon();
        }
    }

    protected function getRecordId(): string
    {
        return $this->scaffold->identifyRecord($this->record);
    }

    protected function getRecordName()
    {
        $output = $this->scaffold->nameRecord($this->record);

        if ($this->scaffold->getRecordNameField() == 'slug') {
            $output = Html::{'samp'}($output);
        }

        return $output;
    }

    protected function getRecordUri(string $id)
    {
        return $this->scaffold->getRecordUriFor($this->record);
    }

    protected function _decorateBody($body)
    {
        return $body;
    }

    protected function _decorate($link)
    {
        return $this->scaffold->decorateRecordLink($link, $this);
    }
}
