<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\fire;

use DecodeLabs\Archetype\Scanner;
use DecodeLabs\Archetype\ScannerTrait;
use DecodeLabs\R7\Legacy;
use Generator;
use ReflectionClass;

class CategoryArchetype implements Scanner
{
    use ScannerTrait;

    protected static array $namespaces = [];

    public static function addNamespace(string $namespace): void
    {
        self::$namespaces[] = $namespace;
    }

    public function getInterface(): string
    {
        return Category::class;
    }

    public function getPriority(): int
    {
        return 1;
    }

    public function resolve(string $name): ?string
    {
        return Category::class.'\\'.$name;
    }

    public function scanClasses(): Generator
    {
        foreach (Legacy::getLoader()->lookupClassList('fire/Category') as $name => $class) {
            $ref = new ReflectionClass($class);
            yield $ref->getFileName() => $class;
        }

        yield from $this->scanNamespaceClasses($this->getInterface());

        foreach (static::$namespaces as $namespace) {
            yield from $this->scanNamespaceClasses($namespace);
        }
    }
}
