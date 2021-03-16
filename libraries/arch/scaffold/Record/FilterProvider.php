<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use df\arch\scaffold\Record\Filter;
use df\arch\scaffold\Record\DataProvider;
use df\opal\query\ISelectQuery as SelectQuery;

use DecodeLabs\Tagged\Markup;

interface FilterProvider extends DataProvider
{
    public function newRecordFilter(
        string $key,
        ?string $label=null,
        ?callable $optionGenerator=null,
        bool $required=false
    ): Filter;

    public function newRecordSwitcher(
        ?callable $optionGenerator=null
    ): Filter;

    public function applyRecordFilters(SelectQuery $query): void;

    public function renderRecordSwitchers(?iterable $filters=null): ?Markup;
    public function renderRecordFilters(?string $contextKey=null, ?iterable $filters=null): ?Markup;
}
