<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire;

use DecodeLabs\Archetype\Scanner;

use DecodeLabs\Archetype\ScannerTrait;
use DecodeLabs\R7\Legacy;
use df\fire\Category as FireCategory;

use Generator;
use ReflectionClass;

class CategoryArchetype implements Scanner
{
    use ScannerTrait;

    /**
     * @var array<string>
     */
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
        $classes = [
            FireCategory::class . '\\' . $name, /** @phpstan-ignore-line */
            Category::class . '\\' . $name
        ];

        foreach (static::$namespaces as $namespace) {
            $classes[] = $namespace . '\\' . $name;
        }

        foreach (array_reverse($classes) as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    public function scanClasses(): Generator
    {
        foreach (Legacy::getLoader()->lookupClassList('fire/Category') as $name => $class) {
            $ref = new ReflectionClass($class);
            yield (string)$ref->getFileName() => $class;
        }

        yield from $this->scanNamespaceClasses(
            Category::class
        );

        foreach (static::$namespaces as $namespace) {
            yield from $this->scanNamespaceClasses($namespace);
        }
    }
}
