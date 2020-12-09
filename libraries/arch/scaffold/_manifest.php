<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;

use df\arch\IRequest as DirectoryRequest;

interface IScaffold extends
    core\IRegistryObject,
    core\IContextAware,
    arch\IOptionalDirectoryAccessLock
{
    public function loadNode();
    public function onNodeDispatch(arch\node\INode $node);
    public function loadComponent($name, array $args=null);
    public function loadFormDelegate($name, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, $id);
    public function loadMenu($name, $id);

    public function getPropagatingQueryVars();

    public function getDirectoryTitle();
    public function getDirectoryIcon();
    public function getDirectoryKeyName();

    public function getNodeUri(string $node, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]): DirectoryRequest;
}
