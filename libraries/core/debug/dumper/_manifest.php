<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

// Exceptions
interface IException {}



// Interfaces
interface IInspector {
    public static function getInstanceCount();
    public function inspect($object, $deep=false);
    public function countArrayRefHits($dumpId);
    public function inspectObjectProperties($object, $deep=false);
    public function countObjectHashHits($dumpId);
}


interface INode extends core\IStringProvider {
    public function getDataValue();
    public function getInspector();
}


trait TNode {
    protected $_inspector;

    public function getInspector() {
        return $this->_inspector;
    }
}


interface IImmutableNode extends INode {
    public function isNull();
    public function isBoolean();
    public function getType();
    public function getValue();
}

interface INumberNode extends INode {
    public function getValue();
    public function isFloat();
}

interface IReferenceNode extends INode {
    public function getType();
    public function isArray();
    public function getDumpId();
}

interface IResourceNode extends INode {
    public function getType();
    public function getName(): string;
}

interface IStringNode extends INode {
    public function getValue();
}

interface IStructureNode extends INode {
    public function isArray();
    public function getType();
    public function getDumpId();
    public function getProperties();
}


interface IProperty {

    const VIS_PRIVATE = 0;
    const VIS_PROTECTED = 1;
    const VIS_PUBLIC = 2;

    public function setName($name);
    public function hasName();
    public function getName(): string;

    public function setValue($value);
    public function getValue();
    public function inspectValue(IInspector $inspector);

    public function setVisibility($visibility);
    public function getVisibility();
    public function getVisibilityString();
    public function isPublic();
    public function isProtected();
    public function isPrivate();

    public function isDeep();
    public function canInline();
}
