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
    
    protected static $_defaultSearchFields = [
        'mode' => 1,
        'request' => 4
    ];

    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('mode', 'String', 16)
            ->setDefaultValue('http');
        $schema->addField('request', 'String', 255);

        $schema->addField('seen', 'Integer', 4);
        $schema->addField('firstSeen', 'Timestamp');
        $schema->addField('lastSeen', 'DateTime');

        $schema->addField('archiveDate', 'DateTime')
            ->isNullable(true);

        $schema->addField('missLogs', 'OneToMany', 'missLog', 'miss');
    }

    public function applyPagination(opal\query\IPaginator $paginator) {
        $paginator
            ->setOrderableFields('mode', 'request', 'seen', 'firstSeen', 'lastSeen')
            ->setDefaultOrder('lastSeen DESC', 'seen DESC');

        return $this;
    }

// Block
    public function applyListRelationQueryBlock(opal\query\IReadQuery $query, $relationField) {
        $query->leftJoinRelation($relationField, 'mode', 'request');
    }


// IO
    public function logMiss($request, $mode=null) {
        $this->update([
                'lastSeen' => 'now',
                'archiveDate' => null
            ])
            ->express('seen', 'seen', '+', 1)
            ->where('request', '=', $request)
            ->execute();

        $miss = $this->fetch()
            ->where('request', '=', $request)
            ->toRow();

        if(!$miss) {
            $miss = $this->newRecord([
                    'mode' => $mode ? $mode : $this->context->getRunMode(),
                    'request' => $request,
                    'seen' => 1,
                    'firstSeen' => 'now',
                    'lastSeen' => 'now'
                ])
                ->save();
        }

        return $miss;
    }
}