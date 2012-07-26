<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session\backend;

use df;
use df\core;
use df\user;
use df\opal;

class Sqlite implements user\ISessionBackend {
    
    const ACCESS_UPDATE_THRESHOLD = 10;
    
    protected $_storePath;
    protected $_manifestTable;
    protected $_dataTables = array();
    protected $_dataTransactions = array();
    protected $_lifeTime = 86400; // 24 hours
    
    public function __construct(user\IManager $manager) {
        $application = $manager->getApplication();
        $this->_storePath = $application->getSharedDataStoragePath().'/session/sqlite';
        
        if(!is_dir($this->_storePath)) {
            mkdir($this->_storePath, 0777, true);
        }
        
        // TODO: get life time from config
    }
    
    public function setLifeTime($lifeTime) {
        if($lifeTime instanceof core\time\IDuration) {
            $lifeTime = $lifeTime->getSeconds();
        }
        
        $this->_lifeTime = (int)$lifeTime;
        return $this;
    }
    
    public function getLifeTime() {
        return $this->_lifeTime;
    }
    
    public function idExists($id) {
        //core\debug()->info('idExists');
        
        $this->_connectManifest();
        
        return (bool)$this->_manifestTable->select('count(*) as count')
            ->where('internalId', '=', $id)
            ->orWhere('externalId', '=', $id)
            ->orWhere('transitionId', '=', $id)
            ->toValue('count');
    }
    
    public function insertDescriptor(user\ISessionDescriptor $descriptor) {
        //core\debug()->info('insertDescriptor');
        
        $this->_connectManifest();
        $this->_manifestTable->insert($descriptor)->execute();
        
        return $descriptor;
    }
    
    public function fetchDescriptor($id, $transitionTime) {
        //core\debug()->info('fetchDescriptor');
        
        $this->_connectManifest();
        
        $output = $this->_manifestTable->select('*')
            ->where('externalId', '=', $id)
            ->beginOrWhereClause()
                ->where('transitionId', '=', $id)
                ->where('transitionTime', '>=', $transitionTime)
                ->endClause()
            ->toRow();
            
        if(!empty($output)) {
            $output = user\session\Descriptor::fromArray($output);
        }
        
        return $output;
    }
    
    public function touchSession(user\ISessionDescriptor $descriptor) {
        //core\debug()->info('touchSession');
        
        $this->_connectManifest();
        $values = $descriptor->touchInfo(user\Manager::SESSION_TRANSITION_LIFETIME);
        
        $this->_manifestTable->update($values)
            ->where('internalId', '=', $descriptor->internalId)
            ->execute();
        
        return $descriptor;
    }
    
    public function applyTransition(user\ISessionDescriptor $descriptor) {
        //core\debug()->info('applyTransition');
        
        $this->_connectManifest();
        
        $this->_manifestTable->update(array(
                'accessTime' => $descriptor->getAccessTime(),
                'externalId' => $descriptor->getExternalId(),
                'transitionId' => $descriptor->getTransitionId(),
                'transitionTime' => $descriptor->getTransitionTime()
            ))
            ->where('internalId', '=', $descriptor->getInternalId())
            ->execute();
            
        return $descriptor;
    }
    
    public function killSession(user\ISessionDescriptor $descriptor) {
        //core\debug()->info('killSession');
        
        $this->_connectManifest();
        $id = $descriptor->getInternalId();
        
        if(isset($this->_dataTransactions[$id])) {
            $this->_dataTransactions[$id]->commit();
        }
        
        $this->_manifestTable->delete()
            ->where('internalId', '=', $id)
            ->execute();
            
        @unlink($this->_storePath.'/session_'.$id.'.db');
        unset($this->_dataTables[$id], $this->_dataTransactions[$id]);
        $this->_manifestTable = null;
        
        return $this;
    }
    
    
    
    public function getNamespaceKeys(user\ISessionDescriptor $descriptor, $namespace) {
        ////core\dump('oi');
        //core\debug()->info('getNamespaceKeys');
        
        return $this->_getDataTable($descriptor)
            ->select('key')
            ->where('namespace', '=', $namespace)
            ->orderBy('updateTime')
            ->toList('key');
    }
    
    public function clearNamespace(user\ISessionDescriptor $descriptor, $namespace) {
        //core\debug()->info('clearNamespace');
        
        $this->_getDataTable($descriptor)
            ->delete()
            ->where('namespace', '=', $namespace)
            ->execute();
    }
    
    public function pruneNamespace(user\ISessionDescriptor $descriptor, $namespace, $age) {
        //core\debug()->info('pruneNamespace');
        
        $this->_getDataTable($descriptor)
            ->delete()
            ->where('namespace', '=', $namespace)
            ->where('updateTime', '<', time() - $age)
            ->where('updateTime', '!=', null)
            ->execute();
    }
    
    
    
    public function fetchNode(user\ISessionDescriptor $descriptor, $namespace, $key) {
        //core\debug()->info('fetchNode: '.$key);
        
        $res = $this->_getDataTable($descriptor)->select('*')
            ->where('namespace', '=', $namespace)
            ->where('key', '=', $key)
            ->toRow();
            
        return user\session\Handler::createNode($namespace, $key, $res);
    }
    
    public function fetchLastUpdatedNode(user\ISessionDescriptor $descriptor, $namespace) {
        $res = $this->_getDataTable($descriptor)->select('*')
            ->where('namespace', '=', $namespace)
            ->orderBy('updateTime DESC')
            ->toRow();
            
        if($res) {
            return user\session\Handler::createNode($namespace, $res['key'], $res);
        } else {
            return null;
        }
    }
    
    public function lockNode(user\ISessionDescriptor $descriptor, \stdClass $node) {
        //core\debug()->info('lockNode: '.$node->key);
        
        $this->_beginDataTransaction($descriptor);
        $node->isLocked = true;
        
        return $node;
    }
    
    public function unlockNode(user\ISessionDescriptor $descriptor, \stdClass $node) {
        //core\debug()->info('unlockNode: '.$node->key);
        
        if($transaction = $this->_getDataTransaction($descriptor)) {
            $transaction->commit();
        }
        
        return $node;
    }
    
    public function updateNode(user\ISessionDescriptor $descriptor, \stdClass $node) {
        //core\debug()->info('updateNode: '.$node->key);
        
        if($transaction = $this->_getDataTransaction($descriptor)) {
            if(empty($node->creationTime)) {
                $node->creationTime = time();
                
                $transaction->insert(array(
                        'namespace' => $node->namespace,
                        'key' => $node->key,
                        'value' => serialize($node->value),
                        'creationTime' => $node->creationTime,
                        'updateTime' => $node->updateTime
                    ))
                    ->execute();
            } else {
                $transaction->update(array(
                        'value' => serialize($node->value),
                        'updateTime' => $node->updateTime
                    ))
                    ->where('namespace', '=', $node->namespace)
                    ->where('key', '=', $node->key)
                    ->execute();
            }
        }
        
        return $node;
    }
    
    public function removeNode(user\ISessionDescriptor $descriptor, $namespace, $key) {
        //core\debug()->info('removeNode: '.$key);
        
        $this->_getDataTable($descriptor)
            ->delete()
            ->where('namespace', '=', $namespace)
            ->where('key', '=', $key)
            ->execute();
            
        return $this;
    }

    public function hasNode(user\ISessionDescriptor $descriptor, $namespace, $key) {
        //core\debug()->info('hasNode: '.$key);
        
        return (bool)$this->_getDataTable($descriptor)
            ->select('count(*) as count')
            ->where('namespace', '=', $namespace)
            ->where('key', '=', $key)
            ->toValue('count');
    }
    
    
    
    public function collectGarbage() {
        //core\debug()->info('collectGarbage');
        
        $this->_connectManifest();
        $delete = false;
        
        foreach($this->_manifestTable->select('*')
            ->where('accessTime', '<', time() - $this->_lifeTime)
            as $session) {
            $delete = true;
            
            $path = $this->_storePath.'/session_'.$session['internalId'].'.db';
            @unlink($path);
        }
        
        if($delete) {
            $this->_manifestTable->delete()
                ->where('accessTime', '<', time() - $this->_lifeTime)
                ->execute();
        }
            
        return $this;
    }
    
    
    
    
    protected function _connectManifest() {
        if($this->_manifestTable) {
            return;
        }
        
        $path = $this->_storePath.'/manifest.db';
        $fileExists = file_exists($path);
        
        $adapter = opal\rdbms\adapter\Base::factory('sqlite://'.$path);
        $adapter->executeSql('PRAGMA synchronous=OFF');
        $adapter->executeSql('PRAGMA count_changes=OFF');
        
        if(!$fileExists) {
            $schema = $adapter->newSchema('sessions');
            
            // Fields
            $schema->addField('internalId', 'text', 40);
            $schema->addField('externalId', 'text', 40);
            $schema->addField('transitionId', 'text', 40)->isNullable(true);
            $schema->addField('startTime', 'integer');
            $schema->addField('transitionTime', 'integer')->isNullable(true);
            $schema->addField('accessTime', 'integer');
            $schema->addField('userId', 'text')->isNullable(true);
            
            // Indexes
            $schema->addPrimaryIndex('internalId', 'internalId');
            $schema->addUniqueIndex('externalId', 'externalId');
            $schema->addUniqueIndex('transitionId', 'transitionId');
            $schema->addIndex('accessTime', 'accessTime');
            
            $table = $adapter->createTable($schema);
        } else {
            $table = $adapter->getTable('sessions');
        }
        
        $this->_manifestTable = $table;
    }

    protected function _getDataTable(user\ISessionDescriptor $descriptor) {
        $id = $descriptor->getInternalId();
        
        if(isset($this->_dataTables[$id])) {
            return $this->_dataTables[$id];
        }
        
        $path = $this->_storePath.'/session_'.$id.'.db';
        $fileExists = file_exists($path);
        
        $adapter = opal\rdbms\adapter\Base::factory('sqlite://'.$path);
        $adapter->executeSql('PRAGMA synchronous=OFF');
        $adapter->executeSql('PRAGMA count_changes=OFF');
        
        if(!$fileExists) {
            $schema = $adapter->newSchema('sessionData');
            
            // Fields
            $schema->addField('namespace', 'text');
            $schema->addField('key', 'text');
            $schema->addField('value', 'blob');
            $schema->addField('creationTime', 'integer');
            $schema->addField('updateTime', 'integer');
            
            // Indexes
            $schema->addPrimaryIndex('primary', array('namespace', 'key'));
            
            $table = $adapter->createTable($schema);
        } else {
            $table = $adapter->getTable('sessionData');
        }
        
        $this->_dataTables[$id] = $table;
        return $table;
    }


    protected function _getDataTransaction(user\ISessionDescriptor $descriptor) {
        $table = $this->_getDataTable($descriptor);
        $id = $descriptor->getInternalId();
        
        if(isset($this->_dataTransactions[$id])) {
            return $this->_dataTransactions[$id];
        }
        
        return null;
    }
    
    protected function _beginDataTransaction(user\ISessionDescriptor $descriptor) {
        $table = $this->_getDataTable($descriptor);
        $id = $descriptor->getInternalId();
        
        if(isset($this->_dataTransactions[$id])) {
            $output = $this->_dataTransactions[$id];
            $output->beginAgain();
        } else {
            $output = $this->_dataTransactions[$id] = $table->begin();
        }
        
        return $output;
    }
}
