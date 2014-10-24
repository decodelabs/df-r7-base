<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\client;

use df;
use df\core;
use df\axis;
use df\opal;

class Unit extends axis\unit\table\Base {
    
    protected function _onCreate(axis\schema\ISchema $schema) {
        $schema->addField('id', 'AutoId', 8);
        $schema->addUniqueField('email', 'String', 255);
        $schema->addField('fullName', 'String', 255);
        $schema->addField('nickName', 'String', 128)->isNullable(true);
        $schema->addField('joinDate', 'Date');
        $schema->addIndexedField('loginDate', 'DateTime')->isNullable(true);
        
        $schema->addField('groups', 'ManyToMany', 'group', 'users')
            ->isDominant(true)
            ->setBridgeUnitId('groupBridge')
            ;
            
        $schema->addField('authDomains', 'OneToMany', 'auth', 'user');
        $schema->addField('rememberKeys', 'OneToMany', 'rememberKey', 'user');
        $schema->addField('options', 'OneToMany', 'option', 'user');
        $schema->addIndexedField('status', 'Integer', 1)->setDefaultValue(3);
        
        $schema->addField('timezone', 'String', 32)->setDefaultValue('UTC');
        $schema->addField('country', 'KeyString', 2, core\string\ICase::UPPER)->setDefaultValue('GB');
        $schema->addField('language', 'KeyString', 2, core\string\ICase::LOWER)->setDefaultValue('en');
    }

    public function applyPagination(opal\query\IPaginator $paginator) {
        $paginator
            ->setOrderableFields(
                'email', 'fullName', 'nickName', 'status', 'joinDate',
                'loginDate', 'timezone', 'country', 'language'
            )
            ->setDefaultOrder('fullName');

        return $this;
    }


    public function emailExists($email) {
        $output = $this->select('id')->where('email', '=', $email)->toValue('id');

        if($output === null) {
            $output = false;
        }

        return $output;
    }

    public function fetchByEmail($email) {
        return $this->fetch()->where('email', '=', $email)->toRow();
    }

    public function fetchActive() {
        if(!$this->context->user->isLoggedIn()) {
            return null;
        }

        return $this->fetch()
            ->where('id', '=', $this->context->user->client->getId())
            ->toRow();
    }

    public function fetchDetailsForMail($id) {
        $output = $this->select('id', 'fullName as name', 'email')
            ->where('id', '=', $id)
            ->toRow();

        if($output) {
            $parts = explode(' ', $output['name'], 2);
            $output['firstName'] = array_shift($parts);
            $output['surname'] = array_shift($parts);
        }

        return $output;
    }



// Query blocks
    public function applyLinkRelationQueryBlock(opal\query\IReadQuery $query, $relationField) {
        if($query instanceof opal\query\ISelectQuery) {
            $query->leftJoinRelation($relationField, 'id as '.$relationField.'Id', 'fullName as '.$relationField.'Name')
                ->combine($relationField.'Id as id', $relationField.'Name as fullName')
                    ->nullOn('id')
                    ->asOne($relationField)
                ->paginate()
                    ->addOrderableFields($relationField.'Name')
                    ->end();
        } else {
            $query->populateSelect($relationField, 'id', 'fullName');
        }
    }
}
