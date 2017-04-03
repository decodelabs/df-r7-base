<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint\plan;

use df;
use df\core;
use df\apex;
use df\axis;
use df\flex;

class Unit extends axis\unit\table\Base {

    const ORDERABLE_FIELDS = ['remoteId', 'creationDate'];
    const DEFAULT_ORDER = 'creationDate DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('name', 'Text', 128);
        $schema->addField('weight', 'Number', 2);
        $schema->addField('creationDate', 'Timestamp');

        $schema->addField('amount', 'Number:Currency');
        $schema->addField('currency', 'Text', 3, flex\ICase::UPPER)
            ->setDefaultValue('USD');

        $schema->addField('interval', 'Enum', 'axis://mint/Interval')
            ->setDefaultValue('month');
        $schema->addField('intervalCount', 'Number:UInteger', 2)
            ->setDefaultValue(1);

        $schema->addField('statementDescriptor', 'Text', 22)
            ->isNullable(true);
        $schema->addField('trialDays', 'Number:UInteger', 2)
            ->isNullable(true);

        $schema->addField('isActive', 'Boolean');
        $schema->addField('isPublic', 'Boolean')
            ->setDefaultValue(true);

        $schema->addField('subscriptions', 'OneToMany', 'subscription', 'plan');
    }
}