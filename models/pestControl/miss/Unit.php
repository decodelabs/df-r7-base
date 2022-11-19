<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl\miss;

use df\axis;
use df\opal;

class Unit extends axis\unit\Table
{
    public const SEARCH_FIELDS = [
        'mode' => 1,
        'request' => 4
    ];

    public const ORDERABLE_FIELDS = [
        'mode', 'request', 'seen', 'botsSeen', 'firstSeen', 'lastSeen'
    ];

    public const DEFAULT_ORDER = ['lastSeen DESC', 'seen DESC'];

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
    public function applyListRelationQueryBlock(opal\query\ISelectQuery $query, opal\query\IField $relationField)
    {
        $query->leftJoinRelation($relationField, 'mode', 'request');
    }



    protected $_requestMatch = [
        // Hack attempts
        '111.tar.gz',
        'adminer.php',
        'backup',
        'blogs.php?action=new_post',
        'cgi-bin',
        'config/AspCms_Config.asp',
        'conn.asp',
        'data/cache_template/',
        'httpdoc',
        'kindeditor/',
        'member/OrderInfo.asp',
        'phpmyadmin',
        'plus/',
        'public.zip',
        'public.rar',
        'uploadfile/',
        'wallet.dat',
        'wp-admin',
        'wp-login',
        'x.php',
        'xmlrpc.php',

        // Meta
        'ads.txt',
        'sitemap.xml',
    ];

    public function checkRequest(string $request): bool
    {
        foreach ($this->_requestMatch as $match) {
            if (stristr($request, $match)) {
                return false;
            }
        }

        return true;
    }
}
