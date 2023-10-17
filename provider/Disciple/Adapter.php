<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Disciple;

use DecodeLabs\Disciple\Adapter\GateKeeper as GateKeeperAdapter;

use DecodeLabs\Disciple\Client;
use DecodeLabs\Disciple\Client\Generic as GenericClient;
use DecodeLabs\Disciple\GateKeeper as GateKeeperInterface;
use DecodeLabs\Disciple\Profile;
use DecodeLabs\Exceptional;

use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;
use df\user\Manager;

use Throwable;

class Adapter implements GateKeeperAdapter
{
    protected Manager $manager;
    protected ?Client $client = null;

    public function __construct()
    {
        $this->manager = Manager::getInstance();
    }

    public function isLoggedIn(): bool
    {
        return $this->manager->isLoggedIn();
    }

    public function getIdentity(): ?string
    {
        return $this->manager->getClient()->getEmail();
    }

    public function getProfile(): Profile
    {
        return $this->manager->getClient();
    }

    public function getClient(): Client
    {
        if ($this->client) {
            return $this->client;
        }

        switch ($mode = Genesis::$kernel->getMode()) {
            case 'Http':
                try {
                    return new GenericClient(
                        'http',
                        Legacy::$http->getRequest()->getIp(),
                        Legacy::$http->getUserAgent()
                    );
                } catch (Throwable $e) {
                    return new GenericClient(
                        'http',
                        '0.0.0.0',
                        null
                    );
                }

            case 'Task':
                return new GenericClient(
                    'cli',
                    '127.0.0.1',
                    $_SERVER['TERM'] ?? $_SERVER['TERM_PROGRAM'] ?? $_SERVER['TERMINAL'] ?? $_SERVER['SHELL'] ?? null
                );

            default:
                throw Exceptional::UnexpectedValue('Unknown run mode ' . $mode);
        }
    }

    public function isA(string ...$signifiers): bool
    {
        return $this->manager->isA(...$signifiers);
    }



    public function getGateKeeper(): GateKeeperInterface
    {
        return new GateKeeper($this);
    }
}
