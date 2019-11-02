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

use DecodeLabs\Glitch\Stack\Trace;

class Unit extends axis\unit\Table
{
    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addIndexedField('hash', 'Binary', 32);
        $schema->addField('body', 'Text', 'huge');

        $schema->addField('errorLogs', 'OneToMany', 'errorLog', 'stackTrace');
    }

    public function logException(\Throwable $e)
    {
        $stackTrace = Trace::fromException($e);
        return $this->logObject($stackTrace);
    }

    public function logArray(array $trace, $rewind=0)
    {
        $stackTrace = Trace::fromArray($trace, $rewind);
        return $this->logObject($stackTrace);
    }

    public function logObject(Trace $stackTrace)
    {
        return $this->logJson(json_encode($stackTrace));
    }

    public function logJson($json)
    {
        if (empty($json)) {
            return null;
        }

        if (!is_string($json)) {
            $json = json_encode($json);
        }

        $hash = $this->context->data->hash($json);

        $output = $this->fetch()
            ->where('hash', '=', $hash)
            ->toRow();

        if (!$output) {
            $output = $this->newRecord([
                    'hash' => $hash,
                    'body' => $json
                ])
                ->save();
        }

        return $output;
    }
}
