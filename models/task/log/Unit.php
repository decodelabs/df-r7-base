<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\task\log;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {

    protected $_defaultOrderableFields = [
        'request', 'environmentMode', 'startDate', 'runTime'
    ];

    protected $_defaultOrder = 'startDate DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('request', 'Text', 1024);
        $schema->addField('environmentMode', 'Enum', 'core/EnvironmentMode');

        $schema->addField('startDate', 'Timestamp');
        $schema->addField('runTime', 'Duration')
            ->isNullable(true);

        $schema->addField('output', 'Text', 'huge')
            ->isNullable(true);
        $schema->addField('errorOutput', 'Text', 'huge')
            ->isNullable(true);
    }
}