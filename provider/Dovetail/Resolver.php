<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Dovetail;

use DecodeLabs\Archetype\Resolver as ArchetypeResolve;
use DecodeLabs\Dovetail\Config;
use DecodeLabs\R7\Config as R7Config;

class Resolver implements ArchetypeResolve
{
    /**
     * Get mapped interface
     */
    public function getInterface(): string
    {
        return Config::class;
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
        /** @phpstan-ignore-next-line */
        return R7Config::class . '\\' . $name;
    }
}
