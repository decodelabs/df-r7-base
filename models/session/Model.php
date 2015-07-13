<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session;

use df;
use df\core;
use df\axis;
use df\user;

class Model extends axis\Model implements user\session\IBackend {

    protected $_lifeTime = 86400; // 24 hours
    protected $_dataTransactions = [];

// Life time
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
    

// Descriptor
    public function insertDescriptor(user\session\IDescriptor $descriptor) {
        $this->manifest->insert($descriptor)->execute();
        return $descriptor;
    }

    public function fetchDescriptor($id, $transitionTime) {
        $output = $this->manifest->select()
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

    public function touchSession(user\session\IDescriptor $descriptor) {
        $values = $descriptor->touchInfo(user\session\Controller::TRANSITION_LIFETIME);
        
        $this->manifest->update($values)
            ->where('internalId', '=', $descriptor->internalId)
            ->execute();
        
        return $descriptor;
    }

    public function applyTransition(user\session\IDescriptor $descriptor) {
        $this->manifest->update([
                'accessTime' => $descriptor->accessTime,
                'externalId' => $descriptor->externalId,
                'transitionId' => $descriptor->transitionId,
                'transitionTime' => $descriptor->transitionTime
            ])
            ->where('internalId', '=', $descriptor->internalId)
            ->execute();
            
        return $descriptor;
    }

    public function killSession(user\session\IDescriptor $descriptor) {
        $id = $descriptor->internalId;
        
        if(isset($this->_dataTransactions[$id])) {
            $this->_dataTransactions[$id]->commit();
        }
        
        $this->manifest->delete()
            ->where('internalId', '=', $id)
            ->execute();

        $this->data->delete()
            ->where('internalId', '=', $id)
            ->execute();

        unset($this->_dataTransactions[$id]);
        
        return $this;
    }

    public function idExists($id) {
        return (bool)$this->manifest->select('COUNT(*) as count')
            ->where('internalId', '=', $id)
            ->orWhere('externalId', '=', $id)
            ->orWhere('transitionId', '=', $id)
            ->toValue('count');
    }
    

// Bucket
    public function getBucketKeys(user\session\IDescriptor $descriptor, $bucket) {
        return $this->data->select('key')
            ->where('internalId', '=', $descriptor->internalId)
            ->where('namespace', '=', $bucket)
            ->orderBy('updateTime')
            ->toList('key');
    }

    public function pruneBucket(user\session\IDescriptor $descriptor, $bucket, $age) {
        $this->data->delete()
            ->where('internalId', '=', $descriptor->internalId)
            ->where('namespace', '=', $bucket)
            ->where('updateTime', '<', time() - $age)
            ->where('updateTime', '!=', null)
            ->execute();
    }

    public function clearBucket(user\session\IDescriptor $descriptor, $bucket) {
        $this->data->delete()
            ->where('internalId', '=', $descriptor->internalId)
            ->where('namespace', '=', $bucket)
            ->execute();
    }
    
    public function clearBucketForAll($bucket) {
        $this->data->delete()
            ->where('namespace', '=', $bucket)
            ->execute();
    }



// Nodes
    public function fetchNode(user\session\IBucket $bucket, $key) {
        $res = $this->data->select()
            ->where('internalId', '=', $bucket->getDescriptor()->internalId)
            ->where('namespace', '=', $bucket->getName())
            ->where('key', '=', $key)
            ->toRow();
            
        return user\session\Node::create($key, $res);
    }

    public function fetchLastUpdatedNode(user\session\IBucket $bucket) {
        $res = $this->data->select()
            ->where('internalId', '=', $bucket->getDescriptor()->internalId)
            ->where('namespace', '=', $bucket->getName())
            ->orderBy('updateTime DESC')
            ->toRow();
            
        if($res) {
            return user\session\Node::create($res['key'], $res);
        } else {
            return null;
        }
    }

    public function lockNode(user\session\IBucket $bucket, user\session\INode $node) {
        $this->_beginDataTransaction($bucket->getDescriptor());
        $node->isLocked = true;
        
        return $node;
    }

    public function unlockNode(user\session\IBucket $bucket, user\session\INode $node) {
        if($transaction = $this->_getDataTransaction($bucket->getDescriptor())) {
            $transaction->commit();
        }
        
        return $node;
    }

    public function updateNode(user\session\IBucket $bucket, user\session\INode $node) {
        $descriptor = $bucket->getDescriptor();

        if($transaction = $this->_getDataTransaction($descriptor)) {
            if(empty($node->creationTime)) {
                $node->creationTime = time();
                
                $transaction->insert([
                        'internalId' => $descriptor->internalId,
                        'namespace' => $bucket->getName(),
                        'key' => $node->key,
                        'value' => serialize($node->value),
                        'creationTime' => $node->creationTime,
                        'updateTime' => $node->updateTime
                    ])
                    ->execute();
            } else {
                $transaction->update([
                        'value' => serialize($node->value),
                        'updateTime' => $node->updateTime
                    ])
                    ->where('internalId', '=', $descriptor->internalId)
                    ->where('namespace', '=', $bucket->getName())
                    ->where('key', '=', $node->key)
                    ->execute();
            }
        }
        
        return $node;
    }

    public function removeNode(user\session\IBucket $bucket, $key) {
        $this->data->delete()
            ->where('internalId', '=', $bucket->getDescriptor()->internalId)
            ->where('namespace', '=', $bucket->getName())
            ->where('key', '=', $key)
            ->execute();
    }

    public function hasNode(user\session\IBucket $bucket, $key) {
        return (bool)$this->data->select('count(*) as count')
            ->where('internalId', '=', $bucket->getDescriptor()->internalId)
            ->where('namespace', '=', $bucket->getName())
            ->where('key', '=', $key)
            ->toValue('count');
    }

    public function collectGarbage() {
        $time = time() - $this->_lifeTime;

        $this->data->delete()
            ->whereCorrelation('internalId', 'in', 'internalId')
                ->from($this->manifest, 'manifest')
                ->where('manifest.accessTime', '<', $time)
                ->endCorrelation()
            ->beginOrWhereClause()
                ->where('data.updateTime', '!=', null)
                ->where('data.updateTime', '<', $time)
                ->endClause()
            ->beginOrWhereClause()
                ->where('data.updateTime', '=', null)
                ->where('data.creationTime', '<', $time)
                ->endClause()
            ->execute();
        
        $this->manifest->delete()
            ->where('accessTime', '<', $time)
            ->execute();
            
        return $this;
    }


// Recall
    public function generateRecallKey(user\IClient $client) {
        return $this->recall->generateKey($client);
    }

    public function hasRecallKey(user\session\RecallKey $key) {
        return $this->recall->hasKey($key);
    }

    public function destroyRecallKey(user\session\RecallKey $key) {
        $this->recall->destroyKey($key);
        return $this;
    }

    public function purgeRecallKeys() {
        $this->recall->purge();
        return $this;
    }


// Helpers
    protected function _getDataTransaction(user\session\IDescriptor $descriptor) {
        $id = $descriptor->internalId;
        
        if(isset($this->_dataTransactions[$id])) {
            return $this->_dataTransactions[$id];
        }
        
        return null;
    }
    
    protected function _beginDataTransaction(user\session\IDescriptor $descriptor) {
        $id = $descriptor->internalId;
        
        if(isset($this->_dataTransactions[$id])) {
            $output = $this->_dataTransactions[$id];
            $output->beginAgain();
        } else {
            $output = $this->_dataTransactions[$id] = $this->data->begin();
        }
        
        return $output;
    }
}