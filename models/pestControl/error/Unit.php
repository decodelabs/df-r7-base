<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\pestControl\error;

use DecodeLabs\Glitch;
use df\axis;
use df\core;

use df\opal;

class Unit extends axis\unit\Table
{
    public const SEARCH_FIELDS = [
        'type' => 1,
        'message' => 4
    ];

    public const ORDERABLE_FIELDS = [
        'type', 'code', 'file', 'line', 'seen', 'firstSeen', 'lastSeen'
    ];

    public const DEFAULT_ORDER = ['lastSeen DESC', 'seen DESC'];

    protected function createSchema($schema)
    {
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
    public function applyListRelationQueryBlock(opal\query\ISelectQuery $query, opal\query\IField $relationField)
    {
        $query->leftJoinRelation($relationField, 'message as origMessage', 'file', 'line');
    }


    // IO
    public function logException(\Throwable $e)
    {
        while ($prev = $e->getPrevious()) {
            $e = $prev;
        }

        $type = get_class($e);
        $normal = false;

        if (preg_match('/^class@anonymous/', $type)) {
            $reflection = new \ReflectionClass($e);
            $type = $names = [];

            foreach ($reflection->getInterfaces() as $name => $interface) {
                $parts = explode('\\', $name);
                $topName = array_pop($parts);

                if (
                    (
                        !preg_match('/^E[A-Z][a-zA-Z0-9_]+$/', $topName) &&
                        !preg_match('/^([A-Z][a-zA-Z0-9_]+)Exception$/', $topName)
                    ) ||
                    preg_match('/^DecodeLabs\\\\Exceptional\\\\/', $name)
                ) {
                    continue;
                }

                $count = count($parts);

                if (!isset($names[$topName]) || $count > $names[$topName]) {
                    $type[$topName] = $name;
                    $names[$topName] = $count;
                }
            }

            if ($parent = $reflection->getParentClass()) {
                array_unshift($type, $parent->getName());
            }


            $type = implode(' + ', $type);
            $normal = true;
        }

        if (!$normal) {
            $type = core\lang\Util::normalizeClassName($type);
        }

        if (strlen((string)$type) > 255) {
            $type = substr($type, 0, 252) . '...';
        }

        return $this->logError(
            $type,
            $e->getCode(),
            Glitch::normalizePath($e->getFile()),
            $e->getLine(),
            $e->getMessage()
        );
    }

    public function logError($type, $code, $file, $line, $message)
    {
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

        if (!$error) {
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
