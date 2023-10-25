<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail\Config;
use DecodeLabs\Dovetail\ConfigTrait;
use DecodeLabs\Systemic;

class Environment implements Config
{
    use ConfigTrait;

    public static function getDefaultValues(): array
    {
        return [
            'mode' => 'development',
            'binaryPaths' => [],
            'daemonsEnabled' => false,
            'daemonUser' => static::extrapolateDaemonUser(),
            'daemonGroup' => static::extrapolateDaemonGroup()
        ];
    }



    public function getMode(): string
    {
        return $this->data->mode->as('string', [
            'default' => 'testing'
        ]);
    }



    public function getBinaryPath(string $id): string
    {
        return $this->data->binaryPaths->{$id}->as('string', [
            'default' => $id
        ]);
    }



    public function canUseDaemons(): bool
    {
        return $this->data->daemonsEnabled->as('bool');
    }

    public function getDaemonUser(): string
    {
        return $this->data->daemonUser->as('?string') ??
            static::extrapolateDaemonUser();
    }

    protected static function extrapolateDaemonUser(): string
    {
        return Systemic::getCurrentProcess()->getOwnerName();
    }

    public function getDaemonGroup(): string
    {
        return $this->data->daemonGroup->as('?string') ??
            static::extrapolateDaemonGroup();
    }

    protected static function extrapolateDaemonGroup(): string
    {
        return Systemic::getCurrentProcess()->getGroupName();
    }
}
