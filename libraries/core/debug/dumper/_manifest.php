<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;


// Inspector
interface IInspector {
    public static function getInstanceCount(): int;
    public function inspect($object, bool $deep=false): INode;
    public function countArrayHashHits(string $dumpId): int;
    public function inspectObjectProperties($object, bool $deep=false): IStructureNode;
    public function countObjectHashHits(string $dumpId): int;
}



// Node
interface INode extends core\IStringProvider {
    public function getDataValue();
    public function getInspector(): IInspector;
}


trait TNode {

    protected $_inspector;

    public function getInspector(): IInspector {
        return $this->_inspector;
    }
}


interface IImmutableNode extends INode {
    public function isNull(): bool;
    public function isBoolean(): bool;
    public function getType(): string;
    public function getValue();
}

interface INumberNode extends INode {
    public function getValue();
    public function isFloat(): bool;
}

interface IReferenceNode extends INode {
    public function getType(): string;
    public function isArray(): bool;
    public function getDumpId(): string;
}

interface IResourceNode extends INode {
    public function getType(): string;
    public function getName(): string;
}

interface IStringNode extends INode {
    public function getValue(): string;
}

interface IStructureNode extends INode {
    public function isArray(): bool;
    public function getType(): ?string;
    public function getDumpId(): ?string;
    public function getProperties(): array;
}



// Property
interface IProperty {

    const VIS_PRIVATE = 'private';
    const VIS_PROTECTED = 'protected';
    const VIS_PUBLIC = 'public';

    public function setName(?string $name);
    public function hasName(): bool;
    public function getName(): ?string;

    public function setValue($value);
    public function getValue();
    public function inspectValue(IInspector $inspector): INode;

    public function setVisibility(string $visibility);
    public function getVisibility(): string;
    public function isPublic(): bool;
    public function isProtected(): bool;
    public function isPrivate(): bool;

    public function isDeep(): bool;
    public function canInline(): bool;
}
