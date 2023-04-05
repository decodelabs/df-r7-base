<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\scaffold\Record;

use DecodeLabs\Tagged as Html;

use DecodeLabs\Tagged\Markup;
use df\opal\query\ISelectQuery as SelectQuery;

trait FilterProviderTrait
{
    protected $recordFilters = null;
    protected $recordSwitchers = null;

    /**
     * Generate new filter object
     */
    public function newRecordFilter(
        string $key,
        ?string $label = null,
        ?callable $optionGenerator = null,
        bool $required = false
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
        ?callable $optionGenerator = null
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
    public function applyRecordFilters(SelectQuery $query, ?string $contextKey = null): void
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
    public function renderRecordSwitchers(?iterable $filters = null, ?string $label = null): ?Markup
    {
        if ($filters === null) {
            $filters = $this->getRecordSwitchers();
        }

        if (empty($filters)) {
            return null;
        }

        $index = [];

        foreach ($filters as $filter) {
            $index[$filter->getKey()] = $filter;
        }

        $keyName = $this->getRecordKeyName();
        $queryValue = $this->request[$keyName];

        $form = $this->html->form(null, 'get');
        $form->addFieldSet()->addClass('scaffold switcher')->push(
            (!empty($queryValue) && !isset($index[$keyName])) ?
                $this->html->hidden($keyName, $this->getRecordId()) : null,
            function () use ($keyName, $index) {
                foreach ($this->request->query as $key => $var) {
                    if (
                        $key === $keyName ||
                        isset($index[$key]) ||
                        in_array($key, ['pg', 'of', 'lm'])
                    ) {
                        continue;
                    }

                    yield $this->html->hidden($key, $var);
                }
            },
            Html::label($label ?? 'Switch'),
            Html::{'div.inputs'}(function () use ($index) {
                foreach ($index as $filter) {
                    $type = $filter->isGrouped() ? 'groupedSelect' : 'select';

                    yield $this->html->{$type}(
                        $filter->getKey(),
                        $filter->getValue(),
                        iterator_to_array($filter->getOptions())
                    )
                        ->isRequired(true)
                        ->setNoSelectionLabel($filter->getLabel() ?? 'All')
                        ->setStyle('max-width', '8em');
                }
            })
        );

        return $form;
    }



    /**
     * Render filter groups
     */
    public function renderRecordFilters(?string $contextKey = null, ?iterable $filters = null): ?Markup
    {
        if ($filters === null) {
            $filters = $this->getRecordFilters();
        }

        if (empty($filters)) {
            return null;
        }

        $keyName = $this->getRecordKeyName();
        $index = [];
        $active = [];

        foreach ($filters as $filter) {
            $key = $filter->getKey();

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
            $found = false;

            foreach ($active as $key => $filter) {
                if (isset($request->query->{$key})) {
                    $found = true;
                    unset($request->query->{$key});
                }
            }

            if (!$found) {
                $request = null;
            }
        } else {
            $request = null;
        }

        $queryValue = $this->request[$keyName];

        $form = $this->html->form(null, 'get');
        $form->addFieldSet()->addClass('scaffold filters')->push(
            !empty($queryValue) && !isset($index[$keyName]) ?
                $this->html->hidden($keyName, $this->getRecordId()) : null,
            isset($this->request[$contextKey]) && !isset($index[$contextKey]) ?
                $this->html->hidden($contextKey, $this->request[$contextKey]) : null,
            isset($this->request['od']) ?
                $this->html->hidden('od', $this->request['od']) : null,
            isset($this->request['search']) ?
                $this->html->hidden('search', $this->request['search']) : null,
            $request === null ?
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
                        ->setNoSelectionLabel($filter->getLabel() ?? 'All')
                        ->setStyle('max-width', '12em');
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
    protected function mergeFilterListFields(?array $fields, ?string $contextKey = null): ?array
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
