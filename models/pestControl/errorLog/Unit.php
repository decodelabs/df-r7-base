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

class Unit extends axis\unit\Table
{
    const SEARCH_FIELDS = [
        'request' => 3,
        'message' => 2
    ];

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('date', 'Timestamp');

        $schema->addField('error', 'ManyToOne', 'error', 'errorLogs');

        $schema->addField('mode', 'Text', 16)
            ->isNullable(true);
        $schema->addField('request', 'Text', 'medium')
            ->isNullable(true);
        $schema->addField('referrer', 'Text', 'medium')
            ->isNullable(true);

        $schema->addField('message', 'Text', 'medium')
            ->isNullable(true);

        $schema->addField('userAgent', 'One', 'user/agent')
            ->isNullable(true);
        $schema->addField('stackTrace', 'ManyToOne', 'stackTrace', 'errorLogs')
            ->isNullable(true);

        $schema->addField('user', 'One', 'user/client')
            ->isNullable(true);

        $schema->addField('isProduction', 'Boolean')
            ->setDefaultValue(true);
        $schema->addField('isArchived', 'Boolean');
    }

    // IO
    public function logException(\Throwable $e, $request=null)
    {
        while ($prev = $e->getPrevious()) {
            $e = $prev;
        }

        $error = $this->_model->error->logException($e);
        $mode = $this->context->getRunMode();
        $message = $e->getMessage();

        if ($message == $error['message']) {
            $message = null;
        }

        return $this->newRecord([
                'error' => $error,
                'mode' => $mode,
                'request' => $this->_model->normalizeLogRequest($request, $mode),
                'referrer' => $this->_model->getLogReferrer(),
                'message' => $message,
                'userAgent' => $this->_model->logCurrentAgent()['id'],
                'stackTrace' => $this->_model->stackTrace->logException($e),
                'user' => $this->_model->getLogUserId(),
                'isProduction' => $this->context->app->isProduction()
            ])
            ->save();
    }

    public function logDeprecated($message, $request=null)
    {
        $e = new Deprecated($message);
        $message = $e->getMessage();
        $trace = $e->getTrace();

        do {
            $last = array_shift($trace);
        } while (($last['function'] ?? null) == 'logDeprecated');

        array_unshift($trace, $last);

        $file = core\fs\Dir::stripPathLocation($trace[0]['file']);
        $line = $trace[0]['line'];

        $error = $this->_model->error->logError(
            'Deprecated', null,
            $file, $line,
            $message
        );

        $mode = $this->context->getRunMode();

        if ($message == $error['message']) {
            $message = null;
        }

        return $this->newRecord([
                'error' => $error,
                'mode' => $mode,
                'request' => $this->_model->normalizeLogRequest($request, $mode),
                'referrer' => $this->_model->getLogReferrer(),
                'message' => $message,
                'userAgent' => $this->_model->logCurrentAgent()['id'],
                'stackTrace' => $this->_model->stackTrace->logArray($trace),
                'user' => $this->_model->getLogUserId(),
                'isProduction' => $this->context->app->isProduction()
            ])
            ->save();
    }
}
