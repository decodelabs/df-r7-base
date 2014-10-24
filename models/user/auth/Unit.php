<?php

namespace df\apex\models\user\auth;

use df\core;
use df\axis;

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addField('user', 'ManyToOne', 'client', 'authDomains');
        $schema->addField('adapter', 'String', 32);
        $schema->addField('identity', 'String', 255);
        $schema->addField('password', 'Binary', 64)->isNullable(true);
        $schema->addField('bindDate', 'DateTime');
        $schema->addField('loginDate', 'DateTime')->isNullable(true);
        
        $schema->addPrimaryIndex('primary', ['user', 'adapter', 'identity']);
        $schema->addIndex('identity');
    }

    public function fetchLocalClientAdapter() {
        if(!$this->context->user->isLoggedIn()) {
            return null;
        }

        return $this->fetch()
            ->where('user', '=', $this->context->user->client->getId())
            ->where('adapter', '=', 'Local')
            ->toRow();
    }
}
