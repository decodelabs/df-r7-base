<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire;

use DecodeLabs\Archetype\Scanner;
use DecodeLabs\Archetype\ScannerTrait;

use Generator;

class BlockArchetype implements Scanner
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
        return Block::class;
    }

    public function getPriority(): int
    {
        return 1;
    }

    public function resolve(string $name): ?string
    {
        $classes = [
            Block::class . '\\' . $name
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
        yield from $this->scanNamespaceClasses(Block::class, Block::class);

        foreach (static::$namespaces as $namespace) {
            yield from $this->scanNamespaceClasses($namespace, Block::class);
        }
    }
}
