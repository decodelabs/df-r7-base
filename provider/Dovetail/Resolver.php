<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Dovetail;

use DecodeLabs\Archetype\Resolver as ArchetypeResolver;
use DecodeLabs\Dovetail\Config;
use DecodeLabs\R7\Config as R7Config;

class Resolver implements ArchetypeResolver
{
    /**
     * @var array<string>
     */
    protected static array $namespaces = [
        /** @phpstan-ignore-next-line */
        R7Config::class
    ];

    /**
     * Get mapped interface
     */
    public function getInterface(): string
    {
        return Config::class;
    }

    /**
     * Add namespace
     */
    public static function addNamespace(string $namespace): void
    {
        static::$namespaces[] = $namespace;
    }

    /**
     * Get resolver priority
     */
    public function getPriority(): int
    {
        return 1;
    }

    /**
     * Resolve Archetype class location
     */
    public function resolve(string $name): ?string
    {
        $parts = explode('#', $name);
        $name = array_shift($parts);

        foreach (static::$namespaces as $namespace) {
            $class = $namespace . '\\' . $name;

            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }
}
