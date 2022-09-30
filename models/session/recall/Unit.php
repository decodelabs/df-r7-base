<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\session\recall;

use df;
use df\core;
use df\apex;
use df\axis;
use df\user;

use DecodeLabs\R7\Legacy;

class Unit extends axis\unit\Table
{
    public const PURGE_THRESHOLD = '-1 month';
    public const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema)
    {
        $schema->addField('user', 'One', 'user/client');
        $schema->addIndexedField('key', 'Binary', 64);
        $schema->addField('date', 'Timestamp');
        $schema->addPrimaryIndex('primary', ['user', 'key']);
    }

    public function generateKey(user\IClient $client)
    {
        $output = user\session\RecallKey::generate($client->getId());
        $passKey = Legacy::getPassKey();

        $this->newRecord([
                'user' => $client->getId(),
                'key' => core\crypt\Util::passwordHash($output->key, $passKey)
            ])
            ->save();

        return $output;
    }

    public function hasKey(user\session\RecallKey $key)
    {
        $passKey = Legacy::getPassKey();

        return (bool)$this->select()
            ->where('user', '=', $key->userId)
            ->where('key', '=', core\crypt\Util::passwordHash($key->key, $passKey))
            ->count();
    }

    public function destroyKey(user\session\RecallKey $key)
    {
        $passKey = Legacy::getPassKey();

        $this->delete()
            ->where('user', '=', $key->userId)
            ->where('key', '=', core\crypt\Util::passwordHash($key->key, $passKey))
            ->execute();

        return $this;
    }

    public function purge()
    {
        $this->delete()
            ->where('date', '<', self::PURGE_THRESHOLD)
            ->execute();

        return $this;
    }
}
