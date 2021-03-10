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
    protected $recordSwitchers = null;

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
     * Generate new switcher filter object
     */
    public function newRecordSwitcher(
        ?callable $optionGenerator=null
    ): Filter {
        return new Filter(
            $this->getRecordKeyName(),
            null,
            $optionGenerator,
            true
        );
    }


    /**
     * Apply clauses to query
     */
    public function applyRecordFilters(SelectQuery $query, ?string $contextKey=null): void
    {
        foreach ($this->getRecordFilters() as $filter) {
            if ($filter->isOverridden($contextKey)) {
                continue;
            }

            $filter->apply($query);
        }
    }


    /**
     * Render record switchers
     */
    public function renderRecordSwitchers(): ?Markup
    {
        $filters = $this->getRecordSwitchers();

        if (empty($filters)) {
            return null;
        }

        $keyName = $this->getRecordKeyName();

        $form = $this->html->form(null, 'get');
        $form->addFieldSet()->addClass('scaffold switcher')->push(
            isset($this->request['od']) ?
                $this->html->hidden('od', $this->request['od']) : null,

            isset($this->request['search']) ?
                $this->html->hidden('search', $this->request['search']) : null,

            Html::label('Switch'),

            Html::{'div.inputs'}(function () use ($filters) {
                foreach ($filters as $filter) {
                    $type = $filter->isGrouped() ? 'groupedSelect' : 'select';

                    yield $this->html->{$type}(
                            $filter->getKey(),
                            $filter->getValue(),
                            iterator_to_array($filter->getOptions())
                        )
                        ->isRequired(true)
                        ->setNoSelectionLabel($filter->getLabel() ?? 'All');
                }
            })
        );

        return $form;
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
        $active = [];

        foreach ($filters as $key => $filter) {
            if (
                $filter->isOverridden($contextKey) ||
                $key === $contextKey
            ) {
                continue;
            }

            $index[$key] = $filter;

            if (
                !$filter->isRequired() &&
                null !== ($value = $filter->getValue()) &&
                $key !== $contextKey
            ) {
                $active[$key] = $filter;
            }
        }

        if (empty($index)) {
            return null;
        }

        if (!empty($active)) {
            $request = clone $this->context->request;

            foreach ($active as $key => $filter) {
                unset($request->query->{$key});
            }
        } else {
            $request = null;
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

            $request !== null ?
                Html::label('Filter') :
                $this->html->link($request, 'Filter')
                    ->setIcon('cross'),

            Html::{'div.inputs'}(function () use ($index, $contextKey) {
                foreach ($index as $filter) {
                    $required = $filter->isRequired();

                    if ($filter->getKey() === $contextKey) {
                        $required = true;
                    }

                    $type = $filter->isGrouped() ? 'groupedSelect' : 'select';

                    yield $this->html->{$type}(
                            $filter->getKey(),
                            $filter->getValue(),
                            iterator_to_array($filter->getOptions())
                        )
                        ->isRequired($required)
                        ->setNoSelectionLabel($filter->getLabel() ?? 'All');
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


    /**
     * Get cached record switcher
     */
    protected function getRecordSwitchers(): array
    {
        if ($this->recordSwitchers === null) {
            $this->recordSwitchers = [];

            foreach ($this->generateRecordSwitchers() as $filter) {
                if (!$filter->getValueGenerator()) {
                    $filter->setValueGenerator(function () use ($filter) {
                        return $this->request[$filter->getKey()];
                    });
                }

                $this->recordSwitchers[$filter->getKey()] = $filter;
            }
        }

        return $this->recordSwitchers;
    }

    /**
     * Generate switcher filters for main list
     */
    protected function generateRecordSwitchers(): iterable
    {
        return [];
    }


    /**
     * Merge record list filter fields
     */
    protected function mergeFilterListFields(?array $fields, ?string $contextKey=null): ?array
    {
        if ($fields === null) {
            $fields = [];
        }

        foreach ($this->getRecordFilters() as $filter) {
            if (
                $filter->isOverridden($contextKey) ||
                empty($innerFields = $filter->getListFields())
            ) {
                continue;
            }

            $fields = array_merge($innerFields, $fields);
        }

        if (empty($fields)) {
            return null;
        }

        return $fields;
    }
}
