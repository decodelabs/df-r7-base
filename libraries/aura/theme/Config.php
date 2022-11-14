<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\theme;

use df\core;
use df\arch;

use DecodeLabs\R7\Theme\Config as ConfigInterface;

class Config extends core\Config implements ConfigInterface
{
    public const ID = 'Theme';
    public const CACHE_IN_MEMORY = false;

    public function getDefaultValues(): array
    {
        return [
            'default' => 'whitewash'
        ];
    }

    public function getThemeIdFor(string $area): string
    {
        $area = ltrim($area, arch\Request::AREA_MARKER);

        if (isset($this->values[$area])) {
            return $this->values[$area];
        } elseif (isset($this->values['default'])) {
            return $this->values['default'];
        } else {
            return 'shared';
        }
    }

    public function getThemeMap(): array
    {
        return $this->values->toArray();
    }
}
