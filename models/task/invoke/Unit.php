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

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('token', 'String', 32);
        $schema->addField('expiryDate', 'DateTime');
        $schema->addField('request', 'String', 1024);
        $schema->addField('environmentMode', 'Enum', 'core/EnvironmentMode')
            ->isNullable(true);
        $schema->addField('invokeKey', 'String', 32)
            ->isNullable(true);
    }

    public function prepareTask($request, $environmentMode=null, core\time\IDate $expiryDate=null) {
        $request = arch\Request::factory($request);
        $token = md5(uniqid('task', true));
        $parts = explode('://', (string)$request, 2);

        switch(strtolower($environmentMode)) {
            case 'production':
            case 'testing':
            case 'development':
                $environmentMode = strtolower($environmentMode);
                break;

            default:
                $environmentMode = null;
                break;                
        }

        $invoke = $this->newRecord([
                'token' => $token,
                'expiryDate' => $expiryDate ? $expiryDate : '+1 minute',
                'request' => array_pop($parts),
                'environmentMode' => $environmentMode
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