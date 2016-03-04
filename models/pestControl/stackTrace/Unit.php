<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\stackTrace;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\table\Base {

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('hash', 'Binary', 32);
        $schema->addField('body', 'Text', 'huge');

        $schema->addField('errorLogs', 'OneToMany', 'errorLog', 'stackTrace');
    }

    public function logException(\Throwable $e) {
        $stackTrace = core\debug\StackTrace::fromException($e);
        return $this->logObject($stackTrace);
    }

    public function logObject(core\debug\IStackTrace $stackTrace) {
        $json = $stackTrace->toJson();
        return $this->logJson($json);
    }

    public function logJson($json) {
        if(empty($json)) {
            return null;
        }

        if(!is_string($json)) {
            $json = json_encode($json);
        }

        $hash = $this->context->data->hash($json);

        $output = $this->fetch()
            ->where('hash', '=', $hash)
            ->toRow();

        if(!$output) {
            $output = $this->newRecord([
                    'hash' => $hash,
                    'body' => $json
                ])
                ->save();
        }

        return $output;
    }
}