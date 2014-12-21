<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\manifest;

use df;
use df\core;
use df\apex;
use df\axis;
use df\user;

class Unit extends axis\unit\table\Base {

    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('internalId', 'Binary', 20)
            ->isConstantLength(true);
        $schema->addUniqueField('externalId', 'Binary', 20)
            ->isConstantLength(true);
        $schema->addUniqueField('transitionId', 'Binary', 20)
            ->isConstantLength(true)
            ->isNullable(true);

        $schema->addField('startTime', 'Integer');
        $schema->addField('transitionTime', 'Integer', 8)
            ->isNullable(true);
        $schema->addIndexedField('accessTime', 'Integer', 8);
        //$schema->addField('userId', 'String', 64)->isNullable(true);
    }
}