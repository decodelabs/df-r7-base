<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\role;

use df;
use df\core;
use df\axis;

class Unit extends axis\unit\table\Base {

    const DEFAULT_MANIFEST = [
        '30dc3f8f-ee05-c1e8-f701-c05c8cb96c35' => [
            'name' => 'Super user',
            'signifier' => 'developer',
            'priority' => 99999,

            'keys' => [
                ['domain' => '*', 'pattern' => '*']
            ]
        ],

        '5a3603eb-b173-c359-f701-1095c3c86c35' => [
            'name' => 'Full front end access',
            'signifier' => null,
            'priority' => 9999,

            'keys' => [
                ['domain' => 'directory', 'pattern' => '~front/*']
            ]
        ],

        '85598326-b24b-c544-f701-00521eed6c35' => [
            'name' => 'Full admin access',
            'signifier' => 'admin',
            'priority' => 9999,

            'keys' => [
                ['domain' => 'directory', 'pattern' => '~admin/*']
            ]
        ],

        '459a093b-d47a-c91c-f701-30dd68ef6c35' => [
            'name' => 'Full mail center access',
            'signifier' => null,
            'priority' => 9999,

            'keys' => [
                ['domain' => 'directory', 'pattern' => '~mail/*']
            ]
        ],

        '1660509c-d819-c927-f701-e07c48f26c35' => [
            'name' => 'Full devtools access',
            'signifier' => 'developer',
            'priority' => 9999,

            'keys' => [
                ['domain' => 'directory', 'pattern' => '~devtools/*']
            ]
        ]
    ];

    const ORDERABLE_FIELDS = [
        'name', 'signifier', 'priority'
    ];

    const DEFAULT_ORDER = ['priority DESC', 'name ASC'];

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');

        $schema->addField('name', 'Text', 64);
        $schema->addField('signifier', 'Text', 32)
            ->isNullable(true);

        $schema->addField('priority', 'Number', 4)
            ->setDefaultValue(50);

        $schema->addField('groups', 'ManyToMany', 'group', 'roles');
        $schema->addField('keys', 'OneToMany', 'key', 'role');
    }

    public function getDefaultManifest() {
        return self::DEFAULT_MANIFEST;
    }
}
