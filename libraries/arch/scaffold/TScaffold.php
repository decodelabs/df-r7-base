<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df;
use df\core;
use df\arch;
use df\aura;
use df\axis;
use df\opal;
use df\mesh;
use df\flex;
use df\user;

use df\arch\scaffold\Record\DataProvider as RecordDataProvider;

use DecodeLabs\Tagged\Html;
use DecodeLabs\Exceptional;

// Index header bar provider
trait TScaffold_IndexHeaderBarProvider
{
    public function buildIndexHeaderBarComponent(array $args=null)
    {
        return (new arch\scaffold\Component\HeaderBar($this, 'index', $args))
            ->setTitle($this->getDirectoryTitle())
            ->setBackLinkRequest($this->getIndexBackLinkRequest());
    }

    protected function getIndexBackLinkRequest()
    {
        return $this->uri->backRequest('../');
    }
}

trait TScaffold_RecordIndexHeaderBarProvider
{
    public function generateIndexOperativeLinks(): iterable
    {
        if (!$this->canAddRecords()) {
            return;
        }

        $recordAdapter = $this->getRecordAdapter();

        yield 'add' => $this->html->link(
                $this->uri($this->getNodeUri('add'), true),
                $this->_('Add '.$this->getRecordItemName())
            )
            ->setIcon('add')
            ->chainIf($recordAdapter instanceof axis\IUnit, function ($link) use ($recordAdapter) {
                $link->addAccessLock($recordAdapter->getEntityLocator()->toString().'#add');
            });
    }
}
