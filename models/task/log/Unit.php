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
use df\opal;

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('request', 'String', 1024);
        $schema->addField('environmentMode', 'Enum', 'core/EnvironmentMode');

        $schema->addField('startDate', 'Timestamp');
        $schema->addField('runTime', 'Duration')
            ->isNullable(true);

        $schema->addField('output', 'BigString', 'huge')
            ->isNullable(true);
        $schema->addField('errorOutput', 'BigString', 'huge')
            ->isNullable(true);
    }

    public function applyPagination(opal\query\IPaginator $paginator) {
        $paginator
            ->setOrderableFields('request', 'environmentMode', 'startDate', 'runTime')
            ->setDefaultOrder('startDate DESC');

        return $this;
    }
}