<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use df\arch\Scaffold;
use df\arch\IComponent as Component;

use df\arch\component\AttributeList as AttributeListComponent;
use df\arch\component\CollectionList as CollectionListComponent;
use df\arch\component\RecordLink as RecordLinkComponent;

use df\arch\node\Form as FormNode;
use df\arch\node\IDelegate as Delegate;

use df\arch\node\IFormState as FormState;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;

interface Decorator extends Scaffold
{
    // Node builders
    public function buildDeleteDynamicNode(): FormNode;
    public function buildDeleteSelectedDynamicNode(): FormNode;

    // Delegate builders
    public function buildSelectorFormDelegate(FormState $state, FormEventDescriptor $event, string $id): Delegate;

    // Component builders
    public function buildDetailsComponent(array $args): Component;
    public function buildListComponent(array $args): Component;
    public function buildLinkComponent(array $args): Component;

    // List generators
    public function generateCollectionList(array $fields, ?iterable $collection=null): CollectionListComponent;
    public function generateAttributeList(array $fields, $record=true): AttributeListComponent;
    public function renderRecordList($query=null, array $fields=null);

    // Link set generators
    public function decorateRecordLink($link, $component);
    public function generateRecordOperativeLinks(array $record): iterable;

    // Fields
    public function autoDefineNameKeyField(string $fieldName, $list, string $mode, ?string $label=null);
}
