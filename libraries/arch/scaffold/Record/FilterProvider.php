<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use DecodeLabs\Tagged\Markup;

use df\opal\query\ISelectQuery as SelectQuery;

interface FilterProvider extends DataProvider
{
    public function newRecordFilter(
        string $key,
        ?string $label = null,
        ?callable $optionGenerator = null,
        bool $required = false
    ): Filter;

    public function newRecordSwitcher(
        ?callable $optionGenerator = null
    ): Filter;

    public function applyRecordFilters(SelectQuery $query): void;

    public function renderRecordSwitchers(?iterable $filters = null, ?string $label = null): ?Markup;
    public function renderRecordFilters(?string $contextKey = null, ?iterable $filters = null): ?Markup;
}
