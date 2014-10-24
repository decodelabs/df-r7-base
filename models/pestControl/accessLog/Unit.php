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
use df\opal;

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('date', 'Timestamp');

        $schema->addField('mode', 'String', 16)
            ->isNullable(true);
        $schema->addField('code', 'Integer', 2);
        $schema->addField('request', 'String', 255)
            ->isNullable(true);
        $schema->addField('message', 'BigString', 'medium');

        $schema->addField('userAgent', 'One', 'user/agent')
            ->isNullable(true);

        $schema->addField('userId', 'String', 128)
            ->isNullable(true);
        $schema->addField('userName', 'String', 128)
            ->isNullable(true);

        $schema->addField('isProduction', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('isArchived', 'Boolean');
    }

    public function applyPagination(opal\query\IPaginator $paginator) {
        $paginator
            ->setOrderableFields('date', 'mode', 'code', 'request', 'seen')
            ->setDefaultOrder('date DESC');

        return $this;
    }

    public function logAccess($code=403, $request=null, $message=null) {
        return $this->newRecord([
                'mode' => $this->context->getRunMode(),
                'code' => $code,
                'request' => $request,
                'message' => $message,
                'userAgent' => $this->context->data->user->agent->logCurrent(),
                'userId' => $this->_model->getLogUserId(),
                'userName' => $this->_model->getLogUserName(),
                'isProduction' => $this->context->application->isProduction()
            ])
            ->save();
    }
}