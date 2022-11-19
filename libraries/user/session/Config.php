<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\session;

use DecodeLabs\Coercion;

use df\core;

class Config extends core\Config
{
    public const ID = 'Session';

    public function getDefaultValues(): array
    {
        return [
            'gc' => [
                'probability' => null
            ],

            'transitions' => [
                'enabled' => true,
                'probability' => null,
                'lifeTime' => null,
                'cooloff' => null
            ]
        ];
    }


    // GC
    public function getGcProbability(): int
    {
        return (int)Coercion::clampInt(
            $this->values->gc->get('probability', 3),
            1,
            10
        );
    }

    // Transitions
    public function transitionsEnabled(): bool
    {
        return (bool)$this->values->transitions->get('enabled', true);
    }

    public function getTransitionProbability(): int
    {
        return (int)Coercion::clampInt(
            $this->values->transitions->get('probability', 10),
            2,
            15
        );
    }

    public function getTransitionLifeTime(): int
    {
        return (int)Coercion::clampInt(
            $this->values->transitions->get('lifeTime', 30),
            10,
            60
        );
    }

    public function getTransitionCooloff(): int
    {
        return (int)Coercion::clampInt(
            $this->values->transitions->get('cooloff', 20),
            10,
            60
        );
    }
}
