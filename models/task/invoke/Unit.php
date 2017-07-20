<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\invoke;

use df;
use df\core;
use df\apex;
use df\axis;
use df\arch;

class Unit extends axis\unit\Table {

    const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema) {
        $schema->addPrimaryField('token', 'Text', 32);
        $schema->addField('expiryDate', 'Date:Time');
        $schema->addField('request', 'Text', 1024);
        $schema->addField('invokeKey', 'Text', 32)
            ->isNullable(true);
    }

    public function prepareTask($request, core\time\IDate $expiryDate=null) {
        $request = arch\Request::factory($request);
        $token = md5(uniqid('task', true));
        $parts = explode('://', (string)$request, 2);

        $invoke = $this->newRecord([
                'token' => $token,
                'expiryDate' => $expiryDate ?? '+1 minute',
                'request' => array_pop($parts)
            ])
            ->save();

        return $token;
    }

    public function authorize($token) {
        $this->purgeTasks();
        $invokeKey = md5(uniqid('k', true));

        $this->update(['invokeKey' => $invokeKey])
            ->where('invokeKey', '=', null)
            ->where('token', '=', $token)
            ->execute();

        $invoke = $this->fetch()
            ->where('token', '=', $token)
            ->where('invokeKey', '=', $invokeKey)
            ->toRow();

        if(!$invoke) {
            return null;
        }

        $invoke->delete();
        return $invoke;
    }

    public function purgeTasks() {
        $this->delete()
            ->where('expiryDate', '<', 'now')
            ->execute();

        return $this;
    }
}