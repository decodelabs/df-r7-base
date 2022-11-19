<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\content\element;

use df\axis;

class Unit extends axis\unit\Table
{
    public const ORDERABLE_FIELDS = [
        'slug', 'name', 'creationDate', 'lastEditDate'
    ];

    public const DEFAULT_ORDER = 'name ASC';

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('slug', 'Slug');
        $schema->addField('name', 'Text', 128);
        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('lastEditDate', 'Date:Time')
            ->isNullable(true);

        $schema->addField('owner', 'One', 'user/client');
        $schema->addField('body', 'ContentSlot');
    }
}
