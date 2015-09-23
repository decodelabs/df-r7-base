<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\content\element;

use df;
use df\core;
use df\apex;
use df\axis;
use df\opal;

class Unit extends axis\unit\table\Base {

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('slug', 'Slug');
        $schema->addField('name', 'String', 128);
        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('lastEditDate', 'DateTime')
            ->isNullable(true);

        $schema->addField('owner', 'One', 'user/client');
        $schema->addField('body', 'ContentSlot');
    }
}