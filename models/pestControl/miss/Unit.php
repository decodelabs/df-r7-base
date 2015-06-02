<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\miss;

use df;
use df\core;
use df\apex;
use df\axis;
use df\opal;

class Unit extends axis\unit\table\Base {
    
    protected $_defaultSearchFields = [
        'mode' => 1,
        'request' => 4
    ];

    protected $_defaultOrderableFields = [
        'mode', 'request', 'seen', 'botsSeen', 'firstSeen', 'lastSeen'
    ];

    protected $_defaultOrder = ['lastSeen DESC', 'seen DESC'];

    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('mode', 'String', 16)
            ->setDefaultValue('http');
        $schema->addField('request', 'String', 255);

        $schema->addField('seen', 'Integer', 4);
        $schema->addField('botsSeen', 'Integer', 4);
        $schema->addField('firstSeen', 'Timestamp');
        $schema->addField('lastSeen', 'DateTime');

        $schema->addField('archiveDate', 'DateTime')
            ->isNullable(true);

        $schema->addField('missLogs', 'OneToMany', 'missLog', 'miss');
    }

// Block
    public function applyListRelationQueryBlock(opal\query\IReadQuery $query, opal\query\IField $relationField) {
        $query->leftJoinRelation($relationField, 'mode', 'request');
    }


// IO
    public function logMiss($request, $isBot=false, $mode=null) {
        $mode = $mode ? $mode : $this->context->getRunMode();
        $request = $this->_model->normalizeLogRequest($request, $mode);

        $this->update([
                'lastSeen' => 'now',
                'archiveDate' => null
            ])
            ->express('seen', 'seen', '+', 1)
            ->chainIf($isBot, function($query) {
                $query->express('botsSeen', 'botsSeen', '+', 1);
            })
            ->where('request', '=', $request)
            ->execute();

        $miss = $this->fetch()
            ->where('request', '=', $request)
            ->toRow();

        if(!$miss) {
            $miss = $this->newRecord([
                    'mode' => $mode,
                    'request' => $request,
                    'seen' => 1,
                    'botsSeen' => $isBot ? 1:0,
                    'firstSeen' => 'now',
                    'lastSeen' => 'now'
                ])
                ->save();
        }

        return $miss;
    }
}