<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\group;

use df;
use df\core;
use df\axis;

class Unit extends axis\unit\table\Base {
    
    protected $_defaultOrderableFields = [
        'name', 'signifier'
    ];

    protected $_defaultOrder = 'name ASC';

    protected static $_defaultManifest = [
        '77abfc6a-bab7-c3fa-f701-e08615a46c35' => [
            'name' => 'Developers',
            'signifier' => 'developer',
            'roles' => ['30dc3f8f-ee05-c1e8-f701-c05c8cb96c35']
        ],

        '8d9bad9e-720e-c643-f701-b0733ea86c35' => [
            'name' => 'Admins',
            'signifier' => 'admin',
            'roles' => [
                '5a3603eb-b173-c359-f701-1095c3c86c35',
                '85598326-b24b-c544-f701-00521eed6c35',
                '459a093b-d47a-c91c-f701-30dd68ef6c35'
            ]
        ]
    ];

    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('name', 'String', 64);
        $schema->addField('signifier', 'String', 32)
            ->isNullable(true);

        $schema->addField('users', 'ManyToMany', 'client', 'groups');
        $schema->addField('roles', 'ManyToMany', 'role', 'groups')
            ->isDominant(true);
    }

    public function getDefaultManifest() {
        return self::$_defaultManifest;
    }
}
