<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\accessPass;

use df\axis;

class Unit extends axis\unit\Table
{
    public const NAME_FIELD = 'id';

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addField('user', 'One', 'client');

        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('expiryDate', 'Date:Time');
    }
}
