<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\role;

use df;
use df\core;
use df\axis;
use df\opal;

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addField('id', 'AutoId', 4);
        $schema->addField('name', 'String', 64);
        $schema->addField('bindState', 'Integer', 1)->isNullable(true);
        $schema->addField('minRequiredState', 'Integer', 1)->isNullable(true);
        $schema->addField('priority', 'Integer', 4)->setDefaultValue(50);
        $schema->addField('groups', 'ManyToMany', 'group', 'roles');
        $schema->addField('keys', 'OneToMany', 'key', 'role');
        
        $schema->addPrimaryIndex('id');
    }

    public function applyPagination(opal\query\IPaginator $paginator) {
        $paginator
            ->setOrderableFields('name', 'bindState', 'minRequiredState', 'priority')
            ->setDefaultOrder('name ASC');

        return $this;
    }
}
