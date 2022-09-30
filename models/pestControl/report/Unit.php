<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\pestControl\report;

use df\axis;

use DecodeLabs\Genesis;

class Unit extends axis\unit\Table
{
    public const ORDERABLE_FIELDS = [
        'date', 'isProduction'
    ];

    public const DEFAULT_ORDER = 'date DESC';

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('date', 'Timestamp');

        $schema->addField('type', 'Text', 20);
        $schema->addField('body', 'Json');

        $schema->addField('userAgent', 'One', 'user/agent')
            ->isNullable(true);

        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('isProduction', 'Boolean')
            ->setDefaultValue(true);
    }

    public function storeReport(
        string $type,
        array $report
    ) {
        return $this->newRecord([
                'type' => $type,
                'body' => $report,
                'userAgent' => $this->_model->logCurrentAgent()['id'],
                'user' => $this->_model->getLogUserId(),
                'isProduction' => Genesis::$environment->isProduction()
            ])
            ->save();
    }
}
