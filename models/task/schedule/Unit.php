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

class Unit extends axis\unit\table\Base {
    
    protected $_defaultOrderableFields = [
        'request', 'environmentMode', 'priority', 'creationDate', 'lastRun', 'isLive', 'isAuto'
    ];

    protected $_defaultOrder = ['lastRun DESC', 'request ASC'];

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('request', 'String', 1024);
        $schema->addField('environmentMode', 'Enum', 'core/EnvironmentMode')
            ->isNullable(true);

        $schema->addField('priority', 'Enum', 'core/unit/Priority');

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
}