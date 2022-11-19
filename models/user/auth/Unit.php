<?php

namespace df\apex\models\user\auth;

use DecodeLabs\Disciple;

use df\axis;

class Unit extends axis\unit\Table
{
    public const BROADCAST_HOOK_EVENTS = false;

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
