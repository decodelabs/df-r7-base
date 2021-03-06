<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use df\arch\scaffold\Record\Filter;
use df\opal\query\ISelectQuery as SelectQuery;

use DecodeLabs\Tagged\Html;
use DecodeLabs\Tagged\Markup;
use DecodeLabs\Exceptional;

trait FilterProviderTrait
{
    protected $recordFilters = null;

    /**
     * Generate new filter object
     */
    public function newRecordFilter(
        string $key,
        ?string $label=null,
        ?callable $optionGenerator=null,
        bool $required=false
    ): Filter {
        return new Filter(
            $key,
            $label,
            $optionGenerator,
            $required
        );
    }


    /**
     * Apply clauses to query
     */
    public function applyRecordFilters(SelectQuery $query): void
    {
        foreach ($this->getRecordFilters() as $filter) {
            $filter->apply($query);
        }
    }

    /**
     * Render filter groups
     */
    public function renderRecordFilters(?string $contextKey=null): ?Markup
    {
        $filters = $this->getRecordFilters();

        if (empty($filters)) {
            return null;
        }

        $keyName = $this->getRecordKeyName();
        $index = [];

        foreach ($filters as $filter) {
            $index[$filter->getKey()] = $filter;
        }

        $form = $this->html->form(null, 'get');
        $form->addFieldSet()->addClass('scaffold filters')->push(
            isset($this->request[$keyName]) && !isset($index[$keyName]) ?
                $this->html->hidden($keyName, $this->getRecordId()) : null,

            isset($this->request[$contextKey]) && !isset($index[$contextKey]) ?
                $this->html->hidden($contextKey, $this->request[$contextKey]) : null,

            isset($this->request['od']) ?
                $this->html->hidden('od', $this->request['od']) : null,

            isset($this->request['search']) ?
                $this->html->hidden('search', $this->request['search']) : null,

            Html::{'div.inputs'}(function () use ($filters, $contextKey) {
                foreach ($filters as $filter) {
                    $required = $filter->isRequired();

                    if ($filter->getKey() === $contextKey) {
                        $required = true;
                    }

                    yield $this->html->select(
                            $filter->getKey(),
                            $filter->getValue(),
                            iterator_to_array($filter->getOptions())
                        )
                        ->isRequired($required)
                        ->setNoSelectionLabel($filter->getLabel() ?? 'All')
                        ->setAttribute('onchange', 'javascript:submit();');
                }
            })
        );

        return $form;
    }


    /**
     * Get cached record filters
     */
    protected function getRecordFilters(): array
    {
        if ($this->recordFilters === null) {
            $this->recordFilters = [];

            foreach ($this->generateRecordFilters() as $filter) {
                if (!$filter->getValueGenerator()) {
                    $filter->setValueGenerator(function () use ($filter) {
                        return $this->request[$filter->getKey()];
                    });
                }

                $this->recordFilters[$filter->getKey()] = $filter;
            }
        }

        return $this->recordFilters;
    }

    /**
     * Generate filters for main list
     */
    protected function generateRecordFilters(): iterable
    {
        return [];
    }
}
