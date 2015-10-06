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

class Unit extends axis\unit\table\Base {

    protected $_defaultOrderableFields = [
        'request', 'environmentMode', 'priority', 'queueDate', 'lockDate'
    ];

    protected $_defaultOrder = 'queueDate DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('request', 'Text', 1024);
        $schema->addField('environmentMode', 'Enum', 'core/EnvironmentMode')
            ->isNullable(true);

        $schema->addField('priority', 'Enum', 'core/unit/Priority');

        $schema->addIndexedField('queueDate', 'Timestamp');

        $schema->addField('lockDate', 'DateTime')
            ->isNullable(true);
        $schema->addField('lockId', 'Guid')
            ->isNullable(true);
    }
}