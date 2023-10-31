<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\pestControl\stackTrace;

use DecodeLabs\Glitch\Stack\Trace;
use DecodeLabs\R7\Legacy;
use df\axis;

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

    public function logArray(array $trace, $rewind = 0)
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

        $hash = Legacy::hash($json);

        $output = $this->fetch()
            ->where('hash', '=', $hash)
            ->toRow();

        if (!$output) {
            try {
                $output = $this->newRecord([
                        'hash' => $hash,
                        'body' => $json
                    ])
                    ->save();
            } catch (\Throwable $e) {
                $output = null;
            }
        }

        return $output;
    }
}
