<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Theme;

interface Config
{
    public function getThemeIdFor(string $area): string;

    /**
     * @return array<string, string>
     */
    public function getThemeMap(): array;
}
