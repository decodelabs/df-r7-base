<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

// Stack trace
interface IStackTrace extends core\IArrayProvider
{
    public function getCalls(): array;
    public function getFirstCall(): ?IStackCall;
    public function toJsonArray(): array;
    public function toJson(): string;

    public function setMessage(?string $message);
    public function getMessage(): ?string;
}


// Stack call
interface IStackCall extends core\IArrayProvider
{
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

    public function getFile(): ?string;
    public function getLine(): ?int;
    public function getCallingFile(): ?string;
    public function getCallingLine(): ?int;

    public function toJsonArray(): array;
    public function toJson(): string;
}
