<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use df\arch\IComponent as Component;
use df\arch\scaffold\IScaffold as Scaffold;

use df\arch\component\AttributeList as AttributeListComponent;
use df\arch\component\CollectionList as CollectionListComponent;
use df\arch\component\RecordLink as RecordLinkComponent;

use df\arch\node\Form as FormNode;
use df\arch\node\IDelegate as Delegate;

interface Decorator extends Scaffold
{
    // Node builders
    public function buildDeleteDynamicNode(): FormNode;
    public function buildDeleteSelectedDynamicNode(): FormNode;

    // Delegate builders
    public function buildSelectorFormDelegate($state, $event, $id): Delegate;

    // Component builders
    public function buildDetailsComponent(array $args): Component;
    public function buildListComponent(array $args): Component;
    public function buildLinkComponent(array $args): Component;

    // List generators
    public function generateCollectionList(array $fields, ?iterable $collection=null): CollectionListComponent;
    public function generateAttributeList(array $fields, $record=true): AttributeListComponent;
    public function renderRecordList($query=null, array $fields=null, $callback=null, ?string $queryMode=null);

    // Link set generators
    public function decorateRecordLink($link, $component);
    public function getRecordOperativeLinks($record, $mode);

    // Fields
    public function autoDefineNameKeyField(string $fieldName, $list, string $mode, ?string $label=null);
}
