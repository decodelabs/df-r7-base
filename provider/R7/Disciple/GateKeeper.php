<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Disciple;

use DateTime;
use DecodeLabs\Disciple\GateKeeper as GateKeeperInterface;
use DecodeLabs\Disciple\GateKeeper\Attempt;

use DecodeLabs\Disciple\GateKeeperTrait;

use DecodeLabs\R7\Legacy;

class GateKeeper implements GateKeeperInterface
{
    use GateKeeperTrait;


    /**
     * Prepare identity string
     */
    protected function prepareIdentity(string $identity): string
    {
        return strtolower(trim($identity));
    }


    /**
     * Fetch list of login attempts
     *
     * @return array<Attempt>
     */
    protected function fetchAttempts(
        string $identity,
        DateTime $since
    ): array {
        $logins = Legacy::getTable('user/login')->select('date', 'authenticated', 'ip')
            ->where('date', '>', $since)
            ->where('identity', '=', $identity)
            ->orderBy('date DESC');

        $output = [];

        foreach ($logins as $login) {
            $output[] = new Attempt(
                Legacy::prepareDate($login['date']) ?? new DateTime('now'),
                $login['ip'],
                $login['authenticated']
            );
        }

        return $output;
    }


    /**
     * Store login attempt
     */
    protected function storeAttempt(
        string $identity,
        string $ip,
        string $agent,
        bool $success
    ): void {
        Legacy::getTable('user/login')->insert([
                'identity' => $identity,
                'user' => $this->fetchUserId($identity),
                'ip' => $ip,
                'agent' => $agent,
                'authenticated' => $success
            ])
            ->execute();
    }

    /**
     * Fetch user ID from identity
     */
    protected function fetchUserId(string $identity): ?string
    {
        $id = Legacy::getTable('user/client')->select('id')
            ->where('email', '=', $identity)
            ->toValue('id');

        if ($id === null) {
            return null;
        }

        return (string)$id;
    }
}
