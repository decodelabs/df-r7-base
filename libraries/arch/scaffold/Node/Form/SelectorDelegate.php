<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\scaffold\Node\Form;

use df\arch\IComponent as Component;
use df\arch\node\form\SelectorDelegate as SelectorDelegateBase;
use df\arch\node\form\State as FormState;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;
use df\arch\Scaffold;
use df\opal\query\ISelectQuery as SelectQuery;

class SelectorDelegate extends SelectorDelegateBase
{
    protected $scaffold;

    public function __construct(Scaffold $scaffold, FormState $state, FormEventDescriptor $event, string $id)
    {
        $this->scaffold = $scaffold;
        parent::__construct($scaffold->getContext(), $state, $event, $id);
    }

    protected function setDefaultValues(): void
    {
        $name = $this->scaffold->getRecordKeyName();

        if (isset($this->request[$name]) && !isset($this->values->selected)) {
            $this->setSelected($this->request[$name]);
        } else {
            parent::setDefaultValues();
        }
    }

    protected function getBaseQuery(array $fields = null): SelectQuery
    {
        return $this->scaffold->queryRecordList('selector', $fields);
    }

    protected function applyQuerySearch(SelectQuery $query, string $search): void
    {
        $this->scaffold->applyRecordListSearch($query, $search);
    }

    protected function renderCollectionList(?iterable $collection): ?Component
    {
        return $this->apex->component(ucfirst($this->scaffold->getRecordKeyName() . 'List'), [
                'actions' => false
            ])
            ->setCollection($collection);
    }

    protected function getResultId(array $row): string
    {
        return $this->scaffold->identifyRecord($row);
    }

    protected function getResultDisplayName(array $row)
    {
        return $this->scaffold->describeRecord($row);
    }
}
