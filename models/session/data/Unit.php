<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\data;

use df;
use df\core;
use df\apex;
use df\axis;
use df\user;

class Unit extends axis\unit\table\Base {

    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addField('namespace', 'String', 255);
        $schema->addField('key', 'String', 255);
        $schema->addIndexedField('internalId', 'String', 40);
        $schema->addField('value', 'BigBinary', 'huge');
        $schema->addField('creationTime', 'Integer', 8);
        $schema->addField('updateTime', 'Integer', 8)->isNullable(true);
        
        $schema->addPrimaryIndex('primary', ['namespace', 'key', 'internalId']);
    }
}