<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\migrate\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;

class TaskConvertGroups extends arch\task\Action {
    
    protected $_connection;

    protected $_groupMap = [];
    protected $_roleMap = [];

    public function execute() {
        core\dump('This task is deprecated!');
        $this->_connection = $this->data->user->client->getUnitAdapter()->getConnection();

        $newGroupTable = $this->_buildNewTable('group');
        $newRoleTable = $this->_buildNewTable('role');
        $newKeyTable = $this->_buildNewTable('key');

        $clientBridgeTable = $this->_buildNewBridge('client', 'groups');
        $roleBridgeTable = $this->_buildNewBridge('group', 'roles');
        $inviteBridgeTable = $this->_buildNewBridge('invite', 'groups');

        $this->_generateGroupManifest($newGroupTable, $roleBridgeTable);
        $this->_generateRoleManifest($newRoleTable, $newKeyTable);

        $this->_mapGroups($newGroupTable);
        $this->_mapRoles($newRoleTable, $newKeyTable, $roleBridgeTable);

        $this->_mapUsers($clientBridgeTable);
        $this->_mapInvites($inviteBridgeTable);

        $this->_swapTables();
        $this->_clearSchemaCache();
    }

    protected function _buildNewTable($unitName) {
        $this->io->writeLine('Building new '.$unitName.' table');
        $unit = $this->data->user->{$unitName};

        return $this->_generateTable($unit);
    }

    protected function _buildNewBridge($unitName, $fieldName) {
        $this->io->writeLine('Building new bridge for '.$unitName.'->'.$fieldName);
        $unit = $this->data->user->{$unitName};
        $bridgeUnit = $unit->getBridgeUnit($fieldName);
        $targetName = null;

        if($unitName == 'client') {
            $targetName = 'user_client_groups';
        }

        return $this->_generateTable($bridgeUnit, $targetName);
    }

    protected function _generateTable($unit, $targetName=null) {
        $schema = $unit->buildInitialSchema();
        $unit->updateUnitSchema($schema);
        $unit->validateUnitSchema($schema);

        $bridge = new axis\schema\bridge\Rdbms($unit, $this->_connection, $schema);
        $dbSchema = $bridge->createFreshTargetSchema();

        if($targetName === null) {
            $targetName = $dbSchema->getName();
        }

        $dbSchema->setName($targetName.'__new__');

        if($targetName == 'user_client_groups' && $dbSchema->hasField('isLeader')) {
            $dbSchema->removeField('isLeader');
        }

        $newConnection = clone $this->_connection;
        return $newConnection->createTable($dbSchema, true);
    }

    protected function _generateGroupManifest($table, $bridgeTable) {
        $this->io->writeLine('Generating group data');
        $unit = $this->data->user->group;
        $manifest = $unit->getDefaultManifest();

        foreach($manifest as $id => $row) {
            $row['id'] = core\string\Uuid::factory($id);
            $roleIds = $row['roles'];
            unset($row['roles']);

            $table->insert($row)->execute();

            foreach($roleIds as $roleId) {
                $bridgeTable->insert([
                    'group_id' => core\string\Uuid::factory($id),
                    'role_id' => core\string\Uuid::factory($roleId)
                ])->execute();
            }
        }
    }

    protected function _generateRoleManifest($roleTable, $keyTable) {
        $this->io->writeLine('Generating role data');
        $unit = $this->data->user->role;
        $manifest = $unit->getDefaultManifest();

        foreach($manifest as $id => $row) {
            $row['id'] = core\string\Uuid::factory($id);
            $keys = $row['keys'];
            unset($row['keys']);

            $roleTable->insert($row)->execute();

            foreach($keys as $key) {
                $key['id'] = core\string\Uuid::comb();
                $key['role_id'] = $row['id'];
                $keyTable->insert($key)->execute();
            }
        }
    }

    protected function _mapGroups($groupTable) {
        $this->io->writeLine('Mapping groups');
        $oldTable = $this->_connection->getTable('user_group');

        foreach($oldTable->select() as $row) {
            $id = $groupTable->select('id')
                ->where('name', 'matches', $row['name'])
                ->toValue('id');

            if($id) {
                $this->_groupMap[$row['id']] = core\string\Uuid::factory($id);
            } else {
                $groupTable->insert([
                    'id' => $id = core\string\Uuid::comb(),
                    'name' => $row['name']
                ])->execute();

                $this->_groupMap[$row['id']] = $id;
            }
        }
    }

    protected function _mapRoles($roleTable, $keyTable, $roleBridgeTable) {
        $this->io->writeLine('Mapping roles');
        $oldRoleTable = $this->_connection->getTable('user_role');
        $oldKeyTable = $this->_connection->getTable('user_key');
        $oldBridgeTable = $this->_connection->getTable('user_group_roles');

        foreach($oldRoleTable->select() as $row) {
            $id = $roleTable->select('id')
                ->where('name', 'matches', $row['name'])
                ->toValue('id');

            if($id) {
                $this->_roleMap[$row['id']] = core\string\Uuid::factory($id);
            } else {
                $roleTable->insert([
                    'id' => $id = core\string\Uuid::comb(),
                    'name' => $row['name'],
                    'priority' => $row['priority']
                ])->execute();

                foreach($oldKeyTable->select()->where('role_id', '=', $row['id']) as $key) {
                    $key['id'] = core\string\Uuid::comb();
                    $key['role_id'] = $id;
                    $keyTable->insert($key)->execute();
                }

                $this->_roleMap[$row['id']] = $id;
            }
        }

        foreach($oldBridgeTable->select() as $row) {
            $roleBridgeTable->replace([
                'group_id' => $this->_groupMap[$row['group_id']],
                'role_id' => $this->_roleMap[$row['role_id']]
            ])->execute();
        }
    }

    protected function _mapUsers($clientBridge) {
        $this->io->writeLine('Mapping users');
        $oldBridgeTable = $this->_connection->getTable('user_groupBridge');

        if(!$oldBridgeTable->exists()) {
            $oldBridgeTable = $this->_connection->getTable('user_client_groups');
        }

        foreach($oldBridgeTable->select() as $row) {
            if(!isset($this->_groupMap[$row['group_id']])) {
                $this->io->writeErrorLine('Skipping user '.$row['client_id'].' with group '.$row['group_id']);
                continue;
            }

            $clientBridge->insert([
                'client_id' => $row['client_id'],
                'group_id' => $this->_groupMap[$row['group_id']]
            ])->execute();
        }
    }

    protected function _mapInvites($inviteBridge) {
        $this->io->writeLine('Mapping invite');
        $oldBridgeTable = $this->_connection->getTable('user_invite_groups');

        if(!$oldBridgeTable->exists()) {
            return;
        }

        foreach($oldBridgeTable->select() as $row) {
            if(!isset($this->_groupMap[$row['group_id']])) {
                $this->io->writeErrorLine('Skipping user '.$row['invite_id'].' with group '.$row['group_id']);
                continue;
            }

            $inviteBridge->insert([
                'invite_id' => $row['invite_id'],
                'group_id' => $this->_groupMap[$row['group_id']]
            ])->execute();
        }
    }

    protected function _swapTables() {
        $this->io->writeLine('Swapping tables');

        $swapTables = [
            'user_group', 'user_role', 'user_key', 'user_client_groups', 
            'user_groupBridge', 'user_group_roles', 'user_invite_groups'
        ];

        foreach($swapTables as $name) {
            $this->_connection->getTable($name)->drop();
            $table = $this->_connection->getTable($name.'__new__');

            if(!$table->exists()) {
                continue;
            }

            $table->rename($name);
        }
    }

    protected function _clearSchemaCache() {
        $this->io->writeLine('Updating schema cache');
        
        $clearTables = [
            'user_client', 'user_group', 'user_role', 'user_key', 
            'user_client_groups', 'user_groupBridge', 'user_group_roles', 
            'user_invite_groups'
        ];

        $this->_connection->getTable('axis_schemas')->delete()
            ->where('storeName', 'in', $clearTables)
            ->execute();

        axis\schema\Cache::getInstance()->clearAll();
    }
}