<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;

class Fuse implements Config
{
    use ConfigTrait;


    public static function getDefaultValues(): array
    {
        return [
            'dependencies' => []
        ];
    }

    /**
     * @return array<string, string|array<string, string>>
     */
    public function getDependencies(): array
    {
        /** @var array<string, string|array<string, string>> */
        return $this->data->dependencies->toArray();
    }
}
