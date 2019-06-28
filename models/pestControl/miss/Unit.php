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

class Unit extends axis\unit\Table
{
    const SEARCH_FIELDS = [
        'mode' => 1,
        'request' => 4
    ];

    const ORDERABLE_FIELDS = [
        'mode', 'request', 'seen', 'botsSeen', 'firstSeen', 'lastSeen'
    ];

    const DEFAULT_ORDER = ['lastSeen DESC', 'seen DESC'];

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('mode', 'Text', 16)
            ->setDefaultValue('http');
        $schema->addField('request', 'Text', 1024);

        $schema->addField('seen', 'Number', 4);
        $schema->addField('botsSeen', 'Number', 4);
        $schema->addField('firstSeen', 'Timestamp');
        $schema->addField('lastSeen', 'Date:Time');

        $schema->addField('archiveDate', 'Date:Time')
            ->isNullable(true);

        $schema->addField('missLogs', 'OneToMany', 'missLog', 'miss');
    }

    // Block
    public function applyListRelationQueryBlock(opal\query\IReadQuery $query, opal\query\IField $relationField)
    {
        $query->leftJoinRelation($relationField, 'mode', 'request');
    }

    public function checkRequest(string $request): bool
    {
        switch ($request) {
            case 'wp-login.php':
            case 'xmlrpc.php':
            case 'ads.txt':
            case 'sitemap.xml':
            case 'config/AspCms_Config.asp':
                return false;

            default:
                return true;
        }
    }
}
