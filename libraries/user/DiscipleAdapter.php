<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user;

use DecodeLabs\Disciple\Adapter;
use DecodeLabs\Disciple\Profile;

class DiscipleAdapter implements Adapter
{
    protected $manager;

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

    public function isA(string ...$signifiers): bool
    {
        return $this->manager->isA(...$signifiers);
    }
}
