<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\media\version;

use df;
use df\core;
use df\apex;
use df\axis;
use df\opal;

class Unit extends axis\unit\table\Base {

    const NAME_FIELD = 'fileName';

    protected $_defaultOrderableFields = [
        'number', 'fileName', 'fileSize', 'contentType', 'hash', 'creationDate', 'purgeDate'
    ];

    protected $_defaultOrder = 'creationDate DESC';

    protected function createSchema($schema) {
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