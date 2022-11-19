<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch;

use df\arch\IComponent as Component;
use df\arch\IOptionalDirectoryAccessLock as OptionalDirectoryAccessLock;
use df\arch\IRequest as DirectoryRequest;
use df\arch\navigation\menu\IMenu as Menu;
use df\arch\node\form\State as FormState;
use df\arch\node\IDelegate as Delegate;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;
use df\arch\node\INode as Node;
use df\core\IContextAware as ContextAware;
use df\core\IRegistryObject as RegistryObject;

interface Scaffold extends
    RegistryObject,
    ContextAware,
    OptionalDirectoryAccessLock
{
    public function loadNode(): Node;
    public function onNodeDispatch(Node $node);
    public function loadComponent(string $name, array $args = null): Component;
    public function loadFormDelegate(string $name, FormState $state, FormEventDescriptor $event, string $id): Delegate;
    public function loadMenu(string $name, $id): Menu;

    public function getPropagatingQueryVars(): array;

    public function renderDirectoryTitle();
    public function getDirectoryIcon(): ?string;
    public function getDirectoryKeyName(): string;

    public function getNodeUri(string $node, array $query = null, $redirFrom = null, $redirTo = null, array $propagationFilter = []): DirectoryRequest;
    public function getIndexParentUri(): DirectoryRequest;
}
