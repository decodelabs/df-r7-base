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

        $schema->addField('number', 'Integer', 4)
            ->isUnsigned(true)
            ->setDefaultValue(1);

        $schema->addField('file', 'ManyToOne', 'file', 'versions');
        $schema->addField('isActive', 'Boolean');

        $schema->addField('fileName', 'Text', 1024);
        $schema->addField('fileSize', 'Integer', 8);
        $schema->addField('contentType', 'Text', 128);

        $schema->addField('hash', 'Binary', 32);
        $schema->addField('owner', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('creationDate', 'DateTime');
        $schema->addField('purgeDate', 'DateTime')
            ->isNullable(true);

        $schema->addField('notes', 'BigText', 'medium')
            ->isNullable(true);
    }
}