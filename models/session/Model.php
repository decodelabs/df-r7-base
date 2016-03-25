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
    protected $_nodeTransactions = [];

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
        $this->descriptor->insert($descriptor)->execute();
        return $descriptor;
    }

    public function fetchDescriptor($id, $transitionTime) {
        $output = $this->descriptor->select()
            ->where('publicKey', '=', $id)
            ->beginOrWhereClause()
                ->where('transitionKey', '=', $id)
                //->where('transitionTime', '>=', $transitionTime)
                ->endClause()
            ->toRow();

        if(!empty($output)) {
            $output = user\session\Descriptor::fromArray($output);
        }

        return $output;
    }

    public function touchSession(user\session\IDescriptor $descriptor) {
        $values = $descriptor->touchInfo(user\session\IController::TRANSITION_LIFETIME);

        $this->descriptor->update($values)
            ->where('id', '=', $descriptor->id)
            ->execute();

        return $descriptor;
    }

    public function applyTransition(user\session\IDescriptor $descriptor) {
        $this->descriptor->update([
                'accessTime' => $descriptor->accessTime,
                'publicKey' => $descriptor->publicKey,
                'transitionKey' => $descriptor->transitionKey,
                'transitionTime' => $descriptor->transitionTime,
                'user' => $descriptor->userId
            ])
            ->where('id', '=', $descriptor->id)
            ->execute();

        return $descriptor;
    }

    public function killSession(user\session\IDescriptor $descriptor) {
        $id = $descriptor->id;

        if(isset($this->_nodeTransactions[$id])) {
            $this->_nodeTransactions[$id]->commit();
        }

        $this->descriptor->delete()
            ->where('id', '=', $id)
            ->execute();

        $this->node->delete()
            ->where('descriptor', '=', $id)
            ->execute();

        unset($this->_nodeTransactions[$id]);

        return $this;
    }

    public function idExists($id) {
        return (bool)$this->descriptor->select('COUNT(*) as count')
            ->where('id', '=', $id)
            ->orWhere('publicKey', '=', $id)
            ->orWhere('transitionKey', '=', $id)
            ->toValue('count');
    }


// Bucket
    public function getBucketKeys(user\session\IDescriptor $descriptor, $bucket) {
        return $this->node->select('key')
            ->where('descriptor', '=', $descriptor->id)
            ->where('bucket', '=', $bucket)
            ->orderBy('updateTime')
            ->toList('key');
    }

    public function pruneBucket(user\session\IDescriptor $descriptor, $bucket, $age) {
        $this->node->delete()
            ->where('descriptor', '=', $descriptor->id)
            ->where('bucket', '=', $bucket)
            ->where('updateTime', '<', time() - $age)
            ->where('updateTime', '!=', null)
            ->execute();
    }

    public function clearBucket(user\session\IDescriptor $descriptor, $bucket) {
        $this->node->delete()
            ->where('descriptor', '=', $descriptor->id)
            ->where('bucket', '=', $bucket)
            ->execute();
    }

    public function clearBucketForAll($bucket) {
        $this->node->delete()
            ->where('bucket', '=', $bucket)
            ->execute();
    }



// Nodes
    public function fetchNode(user\session\IBucket $bucket, $key) {
        $res = $this->node->select()
            ->where('descriptor', '=', $bucket->getDescriptor()->id)
            ->where('bucket', '=', $bucket->getName())
            ->where('key', '=', $key)
            ->toRow();

        return user\session\Node::create($key, $res);
    }

    public function fetchLastUpdatedNode(user\session\IBucket $bucket) {
        $res = $this->node->select()
            ->where('descriptor', '=', $bucket->getDescriptor()->id)
            ->where('bucket', '=', $bucket->getName())
            ->orderBy('updateTime DESC')
            ->toRow();

        if($res) {
            try {
                return user\session\Node::create($res['key'], $res);
            } catch(\Exception $e) {
                $this->context->logs->logException($e);
                return null;
            }
        } else {
            return null;
        }
    }

    public function lockNode(user\session\IBucket $bucket, user\session\INode $node) {
        $this->_beginNodeTransaction($bucket->getDescriptor());
        $node->isLocked = true;

        return $node;
    }

    public function unlockNode(user\session\IBucket $bucket, user\session\INode $node) {
        if($transaction = $this->_getNodeTransaction($bucket->getDescriptor())) {
            $transaction->commit();
        }

        return $node;
    }

    public function updateNode(user\session\IBucket $bucket, user\session\INode $node) {
        $descriptor = $bucket->getDescriptor();

        if($transaction = $this->_getNodeTransaction($descriptor)) {
            if(empty($node->creationTime)) {
                $node->creationTime = time();

                $transaction->insert([
                        'descriptor' => $descriptor->id,
                        'bucket' => $bucket->getName(),
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
                    ->where('descriptor', '=', $descriptor->id)
                    ->where('bucket', '=', $bucket->getName())
                    ->where('key', '=', $node->key)
                    ->execute();
            }
        }

        return $node;
    }

    public function removeNode(user\session\IBucket $bucket, $key) {
        $this->node->delete()
            ->where('descriptor', '=', $bucket->getDescriptor()->id)
            ->where('bucket', '=', $bucket->getName())
            ->where('key', '=', $key)
            ->execute();
    }

    public function hasNode(user\session\IBucket $bucket, $key) {
        return (bool)$this->node->select('count(*) as count')
            ->where('descriptor', '=', $bucket->getDescriptor()->id)
            ->where('bucket', '=', $bucket->getName())
            ->where('key', '=', $key)
            ->toValue('count');
    }

    public function collectGarbage() {
        $time = time() - $this->_lifeTime;

        $this->node->delete()
            ->whereCorrelation('descriptor', 'in', 'id')
                ->from($this->descriptor, 'descriptor')
                ->where('descriptor.accessTime', '<', $time)
                ->endCorrelation()
            ->beginOrWhereClause()
                ->where('node.updateTime', '!=', null)
                ->where('node.updateTime', '<', $time)
                ->endClause()
            ->beginOrWhereClause()
                ->where('node.updateTime', '=', null)
                ->where('node.creationTime', '<', $time)
                ->endClause()
            ->execute();

        $this->descriptor->delete()
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
    protected function _getNodeTransaction(user\session\IDescriptor $descriptor) {
        $id = $descriptor->id;

        if(isset($this->_nodeTransactions[$id])) {
            return $this->_nodeTransactions[$id];
        }

        return null;
    }

    protected function _beginNodeTransaction(user\session\IDescriptor $descriptor) {
        $id = $descriptor->id;

        if(isset($this->_nodeTransactions[$id])) {
            $output = $this->_nodeTransactions[$id];
            $output->beginAgain();
        } else {
            $output = $this->_nodeTransactions[$id] = $this->node->begin();
        }

        return $output;
    }
}