<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\schedule;

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

        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('lastRun', 'DateTime')
            ->isNullable(true);

        $schema->addField('minute', 'String', 128)
            ->setDefaultValue('*');
        $schema->addField('hour', 'String', 128)
            ->setDefaultValue('*');
        $schema->addField('day', 'String', 128)
            ->setDefaultValue('*');
        $schema->addField('month', 'String', 128)
            ->setDefaultValue('*');
        $schema->addField('weekday', 'String', 128)
            ->setDefaultValue('*');

        $schema->addField('isLive', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('isAuto', 'Boolean')
            ->setDefaultValue(true);
    }

    public function applyPagination(opal\query\IPaginator $paginator) {
        $paginator
            ->setOrderableFields('request', 'environmentMode', 'priority', 'creationDate', 'lastRun', 'isLive', 'isAuto')
            ->setDefaultOrder('lastRun DESC', 'request ASC');

        return $this;
    }
}