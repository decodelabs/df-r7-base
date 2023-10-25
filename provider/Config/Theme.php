<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\R7\Theme\Config as ThemeConfig;
use df\arch\Request;

class Theme implements Config, ThemeConfig
{
    use ConfigTrait;

    public static function getDefaultValues(): array
    {
        return [
            'default' => 'whitewash'
        ];
    }

    public function getThemeIdFor(string $area): string
    {
        $area = ltrim($area, Request::AREA_MARKER);

        if (isset($this->data[$area])) {
            return $this->data->{$area}->as('string');
        } elseif (isset($this->data['default'])) {
            return $this->data->default->as('string');
        } else {
            return 'shared';
        }
    }

    /**
     * @return array<string, string>
     */
    public function getThemeMap(): array
    {
        return $this->data->as('string[]');
    }
}
