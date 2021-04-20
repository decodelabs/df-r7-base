<?php

namespace df\apex\models\user\auth;

use df\core;
use df\axis;

use DecodeLabs\Disciple;

class Unit extends axis\unit\Table
{
    const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema)
    {
        $schema->addField('user', 'ManyToOne', 'client', 'authDomains');
        $schema->addField('adapter', 'Text', 32);
        $schema->addField('identity', 'Text', 255);
        $schema->addField('password', 'Binary', 64)->isNullable(true);
        $schema->addField('bindDate', 'Date:Time');
        $schema->addField('loginDate', 'Date:Time')->isNullable(true);

        $schema->addPrimaryIndex('primary', ['user', 'adapter', 'identity']);
        $schema->addIndex('identity');
    }

    public function fetchLocalClientAdapter()
    {
        if (!Disciple::isLoggedIn()) {
            return null;
        }

        return $this->fetch()
            ->where('user', '=', Disciple::getId())
            ->where('adapter', '=', 'Local')
            ->toRow();
    }
}
