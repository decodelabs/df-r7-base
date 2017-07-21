<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

df\Launchpad::loadBaseClass('core/log/_manifest');



// Location provider
interface ILocationProvider {
    public function getFile(): ?string;
    public function getLine(): ?int;
}

trait TLocationProvider {

    protected $_file;
    protected $_line;

    public function getFile(): ?string {
        return $this->_file;
    }

    public function getLine(): ?int {
        return $this->_line;
    }
}


// Context
interface IContext extends core\log\IGroupNode, core\log\IHandler {
    public function render(): void;
    public function flush();
    public function execute();
}



// Stack trace
interface IStackTrace extends core\log\INode, core\IArrayProvider {
    public function getCalls(): array;
    public function getFirstCall(): ?IStackCall;
    public function toJsonArray(): array;
    public function toJson(): string;

    public function setMessage(?string $message);
    public function getMessage(): ?string;
}


// Stack call
interface IStackCall extends ILocationProvider, core\IArrayProvider {

    const STATIC_METHOD = 1;
    const OBJECT_METHOD = 2;
    const NAMESPACE_FUNCTION = 3;
    const GLOBAL_FUNCTION = 4;

    public function getArgs(): array;
    public function hasArgs(): bool;
    public function countArgs(): int;
    public function getArgString(): string;

    public function getType(): ?string;
    public function getTypeString(): ?string;
    public function isStatic(): bool;
    public function isObject(): bool;
    public function isNamespaceFunction(): bool;
    public function isGlobalFunction(): bool;

    public function getNamespace(): ?string;
    public function hasNamespace(): bool;

    public function getClass(): ?string;
    public function hasClass(): bool;
    public function getClassName(): ?string;

    public function getFunctionName(): ?string;
    public function getSignature(?bool $argString=false): string;

    public function getCallingFile(): ?string;
    public function getCallingLine(): ?int;

    public function toJsonArray(): array;
    public function toJson(): string;
}


// Renderer
interface IRenderer {
    public function render(): string;
}
