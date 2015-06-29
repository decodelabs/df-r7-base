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
    
    protected $_defaultSearchFields = [
        'type' => 1,
        'message' => 4
    ];

    protected $_defaultOrderableFields = [
        'type', 'code', 'file', 'line', 'seen', 'firstSeen', 'lastSeen'
    ];

    protected $_defaultOrder = ['lastSeen DESC', 'seen DESC'];

    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('type', 'String', 255)
            ->isNullable(true);
        $schema->addField('code', 'String', 32)
            ->isNullable(true);
        $schema->addField('message', 'BigString', 'medium');

        $schema->addField('file', 'String', 255);
        $schema->addField('line', 'Integer', 4);

        $schema->addField('seen', 'Integer', 4);
        $schema->addField('firstSeen', 'Timestamp');
        $schema->addField('lastSeen', 'DateTime');

        $schema->addField('archiveDate', 'DateTime')
            ->isNullable(true);

        $schema->addField('errorLogs', 'OneToMany', 'errorLog', 'error');
    }


// Block
    public function applyListRelationQueryBlock(opal\query\IReadQuery $query, opal\query\IField $relationField) {
        $query->leftJoinRelation($relationField, 'message as origMessage', 'file', 'line');
    }


// IO
    public function logException(\Exception $e) {
        return $this->logError(
            get_class($e),
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
            ->where('type', '=', $type)
            ->where('code', '=', $code)
            ->where('file', '=', $file)
            ->where('line', '=', $line)
            ->execute();

        $error = $this->fetch()
            ->where('type', '=', $type)
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