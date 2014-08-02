<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\queue;

use df;
use df\core;
use df\apex;
use df\axis;
use df\opal;

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('request', 'String', 1024);
        $schema->addField('environmentMode', 'Enum', ['development', 'testing', 'production'])
            ->isNullable(true);

        $schema->addField('priority', 'Enum', [
            'trivial', 'low', 'medium', 'high', 'critical'
        ]);

        $schema->addIndexedField('queueDate', 'Timestamp');

        $schema->addField('lockDate', 'DateTime')
            ->isNullable(true);
        $schema->addField('lockId', 'Guid')
            ->isNullable(true);
    }

    public function applyPagination(opal\query\IPaginator $paginator) {
        $paginator
            ->setOrderableFields('request', 'environmentMode', 'priority', 'queueDate', 'lockDate')
            ->setDefaultOrder('queueDate DESC');

        return $this;
    }
}