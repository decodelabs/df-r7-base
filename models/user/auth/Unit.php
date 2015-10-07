<?php

namespace df\apex\models\user\auth;

use df\core;
use df\axis;

class Unit extends axis\unit\table\Base {

    protected function createSchema($schema) {
        $schema->addField('user', 'ManyToOne', 'client', 'authDomains');
        $schema->addField('adapter', 'Text', 32);
        $schema->addField('identity', 'Text', 255);
        $schema->addField('password', 'Binary', 64)->isNullable(true);
        $schema->addField('bindDate', 'Date:Time');
        $schema->addField('loginDate', 'Date:Time')->isNullable(true);

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
