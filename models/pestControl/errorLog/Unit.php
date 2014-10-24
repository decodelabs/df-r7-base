<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\errorLog;

use df;
use df\core;
use df\apex;
use df\axis;
use df\arch;

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('date', 'Timestamp');

        $schema->addField('error', 'ManyToOne', 'error', 'errorLogs');

        $schema->addField('mode', 'String', 16)
            ->isNullable(true);
        $schema->addField('request', 'BigString', 'medium')
            ->isNullable(true);

        $schema->addField('message', 'BigString', 'medium')
            ->isNullable(true);

        $schema->addField('userAgent', 'One', 'user/agent')
            ->isNullable(true);
        $schema->addField('stackTrace', 'ManyToOne', 'stackTrace', 'errorLogs')
            ->isNullable(true);

        $schema->addField('userId', 'String', 128)
            ->isNullable(true);
        $schema->addField('userName', 'String', 128)
            ->isNullable(true);

        $schema->addField('isProduction', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('isArchived', 'Boolean');
    }

// IO
    public function logException(\Exception $e, $request=null) {
        $error = $this->_model->error->logException($e);
        $message = $e->getMessage();

        if($message == $error['message']) {
            $message = null;
        }

        return $this->newRecord([
                'error' => $error,
                'mode' => $this->context->getRunMode(),
                'request' => $this->_model->normalizeLogRequest($request),
                'message' => $message,
                'userAgent' => $this->context->data->user->agent->logCurrent(),
                'stackTrace' => $this->_model->stackTrace->logException($e),
                'userId' => $this->_model->getLogUserId(),
                'userName' => $this->_model->getLogUserName(),
                'isProduction' => $this->context->application->isProduction()
            ])
            ->save();
    }
}