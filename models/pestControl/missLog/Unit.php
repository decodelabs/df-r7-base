<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\missLog;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {

    protected $_defaultSearchFields = [
        'request' => 4,
        'message' => 1
    ];

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('date', 'Timestamp');

        $schema->addField('miss', 'ManyToOne', 'miss', 'missLogs');

        $schema->addField('referrer', 'Text', 255)
            ->isNullable(true);
        $schema->addField('message', 'Text', 'medium')
            ->isNullable(true);

        $schema->addField('userAgent', 'One', 'user/agent')
            ->isNullable(true);

        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('isProduction', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('isArchived', 'Boolean');
    }

    public function logMiss($request=null, $message=null) {
        $agent = $this->context->data->user->agent->logCurrent();

        return $this->newRecord([
                'miss' => $this->_model->miss->logMiss($request, $agent['isBot']),
                'referrer' => $this->_model->getLogReferrer(),
                'message' => $message,
                'userAgent' => $agent,
                'user' => $this->_model->getLogUserId(),
                'isProduction' => $this->context->application->isProduction()
            ])
            ->save();
    }
}