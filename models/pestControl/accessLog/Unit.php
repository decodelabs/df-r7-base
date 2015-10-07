<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\accessLog;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {

    protected $_defaultSearchFields = [
        'code' => [
            'operator' => '=',
            'weight' => 5
        ],
        'request' => 4,
        'message' => 2
    ];

    protected $_defaultOrderableFields = [
        'date', 'mode', 'code', 'request', 'seen'
    ];

    protected $_defaultOrder = 'date DESC';

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('date', 'Timestamp');

        $schema->addField('mode', 'Text', 16)
            ->isNullable(true);
        $schema->addField('code', 'Number', 2);
        $schema->addField('request', 'Text', 255)
            ->isNullable(true);
        $schema->addField('message', 'Text', 'medium');

        $schema->addField('userAgent', 'One', 'user/agent')
            ->isNullable(true);

        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('isProduction', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('isArchived', 'Boolean');
    }

    public function logAccess($code=403, $request=null, $message=null) {
        $mode = $this->context->getRunMode();
        $request = $this->_model->normalizeLogRequest($request, $mode);

        return $this->newRecord([
                'mode' => $mode,
                'code' => $code,
                'request' => $request,
                'message' => $message,
                'userAgent' => $this->context->data->user->agent->logCurrent(),
                'user' => $this->_model->getLogUserId(),
                'isProduction' => $this->context->application->isProduction()
            ])
            ->save();
    }
}