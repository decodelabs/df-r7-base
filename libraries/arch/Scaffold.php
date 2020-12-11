<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df\arch\IRequest as DirectoryRequest;
use df\core\IRegistryObject as RegistryObject;
use df\core\IContextAware as ContextAware;
use df\arch\IOptionalDirectoryAccessLock as OptionalDirectoryAccessLock;
use df\arch\node\INode as Node;
use df\arch\node\IFormState as FormState;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;

interface Scaffold extends
    RegistryObject,
    ContextAware,
    OptionalDirectoryAccessLock
{
    public function loadNode();
    public function onNodeDispatch(Node $node);
    public function loadComponent($name, array $args=null);
    public function loadFormDelegate($name, FormState $state, FormEventDescriptor $event, $id);
    public function loadMenu($name, $id);

    public function getPropagatingQueryVars();

    public function getDirectoryTitle();
    public function getDirectoryIcon();
    public function getDirectoryKeyName();

    public function getNodeUri(string $node, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]): DirectoryRequest;
    public function getIndexParentUri(): DirectoryRequest;
}
