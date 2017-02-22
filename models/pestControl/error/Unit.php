<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\error;

use df;
use df\core;
use df\apex;
use df\axis;
use df\opal;

class Unit extends axis\unit\table\Base {

    const SEARCH_FIELDS = [
        'type' => 1,
        'message' => 4
    ];

    const ORDERABLE_FIELDS = [
        'type', 'code', 'file', 'line', 'seen', 'firstSeen', 'lastSeen'
    ];

    const DEFAULT_ORDER = ['lastSeen DESC', 'seen DESC'];

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('type', 'Text', 255)
            ->isNullable(true);
        $schema->addField('code', 'Text', 32)
            ->isNullable(true);
        $schema->addField('message', 'Text', 'medium');

        $schema->addField('file', 'Text', 255);
        $schema->addField('line', 'Number', 4);

        $schema->addField('seen', 'Number', 4);
        $schema->addField('firstSeen', 'Timestamp');
        $schema->addField('lastSeen', 'Date:Time');

        $schema->addField('archiveDate', 'Date:Time')
            ->isNullable(true);

        $schema->addField('errorLogs', 'OneToMany', 'errorLog', 'error');
    }


// Block
    public function applyListRelationQueryBlock(opal\query\IReadQuery $query, opal\query\IField $relationField) {
        $query->leftJoinRelation($relationField, 'message as origMessage', 'file', 'line');
    }


// IO
    public function logException(\Throwable $e) {
        $type = get_class($e);
        $normal = false;

        if(preg_match('/^class@anonymous/', $type)) {
            $reflection = new \ReflectionClass($e);

            if($parent = $reflection->getParentClass()) {
                $type = [];

                foreach($reflection->getInterfaces() as $name => $interface) {
                    $parts = explode('\\', $name);
                    $topName = array_pop($parts);

                    if(!preg_match('/^E[A-Z][a-zA-Z0-9_]+$/', $topName) && ($topName !== 'IError' || $name === 'df\\core\\IError')) {
                        continue;
                    }

                    if(implode('\\', $parts) == 'df\\core') {
                        array_unshift($type, $topName);
                    } else {
                        $type[] = $name;
                    }
                }

                array_unshift($type, $parent->getName());
                $type = implode(' + ', $type);
                $normal = true;
            }
        }

        if(!$normal) {
            $type = core\lang\Util::normalizeClassName($type);
        }

        return $this->logError(
            $type,
            $e->getCode(),
            core\fs\Dir::stripPathLocation($e->getFile()),
            $e->getLine(),
            $e->getMessage()
        );
    }

    public function logError($type, $code, $file, $line, $message) {
        $this->update([
                'lastSeen' => 'now',
                'archiveDate' => null
            ])
            ->express('seen', 'seen', '+', 1)
            //->where('type', '=', $type)
            ->where('code', '=', $code)
            ->where('file', '=', $file)
            ->where('line', '=', $line)
            ->execute();

        $error = $this->fetch()
            //->where('type', '=', $type)
            ->where('code', '=', $code)
            ->where('file', '=', $file)
            ->where('line', '=', $line)
            ->toRow();

        if(!$error) {
            $error = $this->newRecord([
                    'type' => $type,
                    'code' => $code,
                    'message' => $message,
                    'file' => $file,
                    'line' => $line,
                    'seen' => 1,
                    'firstSeen' => 'now',
                    'lastSeen' => 'now'
                ])
                ->save();
        }

        return $error;
    }
}