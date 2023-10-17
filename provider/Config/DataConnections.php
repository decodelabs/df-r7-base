<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;

class DataConnections implements Config
{
    use ConfigTrait;

    public const DEFAULT_DSN = 'mysql://user:pass@localhost/database';

    public static function getDefaultValues(): array
    {
        return [
            'connections' => [
                'master' => [
                    'adapter' => 'Rdbms',
                    'dsn' => self::DEFAULT_DSN
                ],
                'search' => [
                    'adapter' => 'Elastic'
                ]
            ],
            'units' => [
                'default' => 'master',
                '@search' => 'search'
            ]
        ];
    }
}
