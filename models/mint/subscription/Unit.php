<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint\subscription;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {

    const ORDERABLE_FIELDS = [
        'remoteId', 'creationDate', 'lastUpdateDate',
        'startDate', 'endDate', 'cancelDate',
        'atPeriodEnd', 'periodStart', 'periodEnd',
        'nextAttemptDate'
    ];
    const DEFAULT_ORDER = 'creationDate DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addUniqueField('remoteId', 'Text', 32);
        $schema->addField('customer', 'ManyToOne', 'customer', 'subscriptions');
        $schema->addField('plan', 'ManyToOne', 'plan', 'subscriptions');

        $schema->addField('creationDate', 'Timestamp');
        $schema->addField('lastUpdateDate', 'Date:Time');

        $schema->addField('startDate', 'Date:Time');
        $schema->addField('endDate', 'Date:Time')
            ->isNullable(true);
        $schema->addField('cancelDate', 'Date:Time')
            ->isNullable(true);
        $schema->addField('atPeriodEnd', 'Boolean');

        $schema->addField('periodStart', 'Date:Time')
            ->isNullable(true);
        $schema->addField('periodEnd', 'Date:Time')
            ->isNullable(true);

        $schema->addField('nextAttemptDate', 'Date:Time')
            ->isNullable(true);
    }
}