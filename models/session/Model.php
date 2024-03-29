<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session;

use df\axis;
use df\core;
use df\user;

class Model extends axis\Model implements user\session\IBackend
{
    protected $_lifeTime = 86400; // 24 hours

    // Life time
    public function setLifeTime($lifeTime)
    {
        if ($lifeTime instanceof core\time\IDuration) {
            $lifeTime = $lifeTime->getSeconds();
        }

        $this->_lifeTime = (int)$lifeTime;
        return $this;
    }

    public function getLifeTime(): int
    {
        return $this->_lifeTime;
    }


    // Descriptor
    public function insertDescriptor(user\session\Descriptor $descriptor)
    {
        $this->descriptor->insert($descriptor)->execute();
        return $descriptor;
    }

    public function fetchDescriptor(string $id, ?int $transitionTime): ?user\session\Descriptor
    {
        $output = $this->descriptor->select()
            ->where('publicKey', '=', $id)
            ->beginOrWhereClause()
                ->where('transitionKey', '=', $id)
                //->where('transitionTime', '>=', $transitionTime)
                ->endClause()
            ->toRow();

        if (!empty($output)) {
            $output = user\session\Descriptor::fromArray($output);
        }

        return $output;
    }

    public function touchSession(user\session\Descriptor $descriptor, int $lifeTime = 30)
    {
        $values = $descriptor->touchInfo($lifeTime);

        $this->descriptor->update($values)
            ->where('id', '=', $descriptor->id)
            ->execute();

        return $descriptor;
    }

    public function applyTransition(user\session\Descriptor $descriptor)
    {
        $fields = [
            'accessTime' => $descriptor->accessTime,
            'publicKey' => $descriptor->publicKey,
            'transitionKey' => $descriptor->transitionKey,
            'transitionTime' => $descriptor->transitionTime
        ];

        if ($descriptor->userId !== null) {
            $fields['user'] = $descriptor->userId;
        }

        $this->descriptor->update($fields)
            ->where('id', '=', $descriptor->id)
            ->execute();

        return $descriptor;
    }

    public function killSession(user\session\Descriptor $descriptor)
    {
        $id = $descriptor->id;

        $this->descriptor->delete()
            ->where('id', '=', $id)
            ->execute();

        $this->node->delete()
            ->where('descriptor', '=', $id)
            ->execute();

        return $this;
    }

    public function idExists(string $id): bool
    {
        return (bool)$this->descriptor->select('COUNT(*) as count')
            ->where('id', '=', $id)
            ->orWhere('publicKey', '=', $id)
            ->orWhere('transitionKey', '=', $id)
            ->toValue('count');
    }


    // Bucket
    public function getBucketKeys(user\session\Descriptor $descriptor, string $bucket): array
    {
        return $this->node->select('key')
            ->where('descriptor', '=', $descriptor->id)
            ->where('bucket', '=', $bucket)
            ->orderBy('updateTime')
            ->toList('key');
    }

    public function pruneBucket(user\session\Descriptor $descriptor, string $bucket, int $age)
    {
        $this->node->delete()
            ->where('descriptor', '=', $descriptor->id)
            ->where('bucket', '=', $bucket)
            ->where('updateTime', '<', time() - $age)
            ->where('updateTime', '!=', null)
            ->execute();
    }


    public function getBuckets(user\session\Descriptor $descriptor): array
    {
        return $this->node->select('bucket')
            ->where('descriptor', '=', $descriptor->id)
            ->toList('bucket');
    }

    public function getBucketsLike(user\session\Descriptor $descriptor, string $bucket, string $operator = null): array
    {
        return $this->node->select('bucket')
            ->where('bucket', $operator ?? 'like', $bucket)
            ->where('descriptor', '=', $descriptor->id)
            ->toList('bucket');
    }

    public function getBucketsForUserLike(string $userId, string $bucket, string $operator = null): array
    {
        return $this->node->selectDistinct('bucket')
            ->whereCorrelation('descriptor', 'in', 'id')
                ->from('axis://session/Descriptor')
                ->where('user', '=', $userId)
                ->endCorrelation()
            ->where('bucket', $operator ?? 'like', $bucket)
            ->toList('bucket');
    }

    public function getBucketsForAllLike(string $bucket, string $operator = null): array
    {
        return $this->node->selectDistinct('bucket')
            ->where('bucket', $operator ?? 'like', $bucket)
            ->toList('bucket');
    }



    public function clearBucket(user\session\Descriptor $descriptor, string $bucket, string $operator = null)
    {
        $this->node->delete()
            ->where('descriptor', '=', $descriptor->id)
            ->where('bucket', $operator ?? '=', $bucket)
            ->execute();
    }

    public function clearBucketForUser(string $userId, string $bucket, string $operator = null)
    {
        $descriptors = $this->descriptor->select('id')
            ->where('user', '=', $userId)
            ->toArray();

        foreach ($descriptors as $descriptor) {
            $this->node->delete()
                ->where('bucket', $operator ?? '=', $bucket)
                ->where('descriptor', '=', $descriptor['id'])
                ->execute();
        }
    }

    public function clearBucketForAll(string $bucket, string $operator = null)
    {
        $this->node->delete()
            ->where('bucket', $operator ?? '=', $bucket)
            ->execute();
    }



    // Nodes
    public function fetchNode(user\session\IBucket $bucket, $key): user\session\Node
    {
        $res = $this->node->select()
            ->where('descriptor', '=', $bucket->getDescriptor()->id)
            ->where('bucket', '=', $bucket->getName())
            ->where('key', '=', $key)
            ->toRow();

        return user\session\Node::create($key, $res);
    }

    public function fetchLastUpdatedNode(user\session\IBucket $bucket): ?user\session\Node
    {
        $res = $this->node->select()
            ->where('descriptor', '=', $bucket->getDescriptor()->id)
            ->where('bucket', '=', $bucket->getName())
            ->orderBy('updateTime DESC')
            ->toRow();

        if ($res) {
            try {
                return user\session\Node::create($res['key'], $res);
            } catch (\Throwable $e) {
                $this->context->logs->logException($e);
                return null;
            }
        } else {
            return null;
        }
    }

    public function updateNode(user\session\IBucket $bucket, user\session\Node $node)
    {
        $descriptor = $bucket->getDescriptor();

        if (empty($node->creationTime)) {
            $node->creationTime = time();

            $this->node->replace([
                    'descriptor' => $descriptor->id,
                    'bucket' => $bucket->getName(),
                    'key' => $node->key,
                    'value' => serialize($node->value),
                    'creationTime' => $node->creationTime,
                    'updateTime' => $node->updateTime
                ])
                ->execute();
        } else {
            $this->node->update([
                    'value' => serialize($node->value),
                    'updateTime' => $node->updateTime
                ])
                ->where('descriptor', '=', $descriptor->id)
                ->where('bucket', '=', $bucket->getName())
                ->where('key', '=', $node->key)
                ->execute();
        }

        return $node;
    }

    public function removeNode(user\session\IBucket $bucket, string $key)
    {
        $this->node->delete()
            ->where('descriptor', '=', $bucket->getDescriptor()->id)
            ->where('bucket', '=', $bucket->getName())
            ->where('key', '=', $key)
            ->execute();
    }

    public function hasNode(user\session\IBucket $bucket, string $key)
    {
        return (bool)$this->node->select('count(*) as count')
            ->where('descriptor', '=', $bucket->getDescriptor()->id)
            ->where('bucket', '=', $bucket->getName())
            ->where('key', '=', $key)
            ->toValue('count');
    }

    public function collectGarbage()
    {
        // This is now in a background task :)
        return $this;
    }


    // Recall
    public function generateRecallKey(user\IClient $client)
    {
        return $this->recall->generateKey($client);
    }

    public function hasRecallKey(user\session\RecallKey $key): bool
    {
        return $this->recall->hasKey($key);
    }

    public function destroyRecallKey(user\session\RecallKey $key)
    {
        $this->recall->destroyKey($key);
        return $this;
    }

    public function purgeRecallKeys()
    {
        $this->recall->purge();
        return $this;
    }
}
