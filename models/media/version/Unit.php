<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\version;

use df\axis;

class Unit extends axis\unit\Table
{
    public const NAME_FIELD = 'fileName';

    public const ORDERABLE_FIELDS = [
        'number', 'fileName', 'fileSize', 'contentType', 'hash', 'creationDate', 'purgeDate'
    ];

    public const DEFAULT_ORDER = 'creationDate DESC';

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('number', 'Number', 4)
            ->isUnsigned(true)
            ->setDefaultValue(1);

        $schema->addField('file', 'ManyToOne', 'file', 'versions');
        $schema->addField('isActive', 'Boolean');

        $schema->addField('fileName', 'Text', 1024);
        $schema->addField('fileSize', 'Number', 8);
        $schema->addField('contentType', 'Text', 128);

        $schema->addField('hash', 'Binary', 32);
        $schema->addField('owner', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('creationDate', 'Date:Time');
        $schema->addField('purgeDate', 'Date:Time')
            ->isNullable(true);

        $schema->addField('notes', 'Text', 'medium')
            ->isNullable(true);
    }
}
