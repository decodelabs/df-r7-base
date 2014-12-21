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
    
class Unit extends axis\unit\table\Base {

    const PURGE_THRESHOLD = '-1 month';

    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addField('user', 'One', 'user/client');
        $schema->addIndexedField('key', 'Binary', 64);
        $schema->addField('date', 'Timestamp');
        $schema->addPrimaryIndex('primary', ['user', 'key']);
    }

    public function generateKey(user\IClient $client) {
        $output = user\session\RecallKey::generate($client->getId());
        $passKey = df\Launchpad::$application->getPassKey();

        $this->newRecord([
                'user' => $client->getId(),
                'key' => core\string\Util::passwordHash($output->key, $passKey)
            ])
            ->save();

        return $output;
    }

    public function hasKey(user\session\RecallKey $key) {
        $passKey = df\Launchpad::$application->getPassKey();

        return (bool)$this->select()
            ->where('user', '=', $key->userId)
            ->where('key', '=', core\string\Util::passwordHash($key->key, $passKey))
            ->count();
    }

    public function destroyKey(user\session\RecallKey $key) {
        $passKey = df\Launchpad::$application->getPassKey();

        $this->delete()
            ->where('user', '=', $key->userId)
            ->where('key', '=', core\string\Util::passwordHash($key->key, $passKey))
            ->execute();

        return $this;
    }

    public function purge() {
        $this->delete()
            ->where('date', '<', self::PURGE_THRESHOLD)
            ->execute();

        return $this;
    }
}