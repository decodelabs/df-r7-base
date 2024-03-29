<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session;

use df\core;
use df\opal;

class Descriptor implements
    core\IArrayInterchange,
    opal\query\IDataRowProvider
{
    public $id;
    public $publicKey;
    public $transitionKey;
    public $userId;
    public $startTime;
    public $accessTime;
    public $transitionTime;
    public $justStarted = false;

    public static function fromArray(array $values)
    {
        $output = new self(
            $values['id'],
            $values['publicKey']
        );

        foreach ($values as $key => $value) {
            switch ($key) {
                case 'transitionKey':
                    $output->setTransitionKey($value);
                    break;

                case 'user':
                case 'userId':
                    $output->setUserId($value);
                    break;

                case 'startTime':
                    $output->setStartTime($value);
                    break;

                case 'accessTime':
                    $output->setAccessTime($value);
                    break;

                case 'transitionTime':
                    $output->setTransitionTime($value);
                    break;
            }
        }

        return $output;
    }

    public function __construct(string $id, $publicKey)
    {
        $this->setId($id);
        $this->setPublicKey($publicKey);
    }

    public function isNew()
    {
        return $this->startTime == $this->accessTime
            && $this->transitionTime === null;
    }

    public function hasJustStarted(bool $flag = null)
    {
        if ($flag !== null) {
            $this->justStarted = $flag;
            return $this;
        }

        return $this->justStarted;
    }

    public function setId(string $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIdHex()
    {
        return bin2hex($this->id);
    }

    public function setPublicKey($id)
    {
        $this->publicKey = $id;
        return $this;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }

    public function getPublicKeyHex()
    {
        return bin2hex($this->publicKey);
    }

    public function setTransitionKey($id)
    {
        if (empty($id)) {
            $id = null;
        }

        $this->transitionKey = $id;
        return $this;
    }

    public function getTransitionKey()
    {
        return $this->transitionKey;
    }

    public function getTransitionKeyHex()
    {
        return bin2hex($this->transitionKey);
    }

    public function applyTransition($newPublicKey)
    {
        $this->setTransitionKey($this->publicKey);
        $this->transitionTime = $this->accessTime = time();
        return $this->setPublicKey($newPublicKey);
    }

    public function hasJustTransitioned($transitionLifeTime = 10)
    {
        return time() - $this->transitionTime < $transitionLifeTime;
    }

    public function needsTouching($transitionLifeTime = 10)
    {
        if ($this->justStarted) {
            return false;
        }

        $time = time();
        $output = true;

        if ($time - $this->transitionTime < $transitionLifeTime) {
            $output = false;
        }

        if ($time - $this->accessTime < $transitionLifeTime) {
            $output = false;
        }

        return $output;
    }

    public function touchInfo($transitionLifeTime = 10)
    {
        $output = [
            'accessTime' => $this->accessTime = time()
        ];

        if ($this->accessTime - $this->transitionTime >= $transitionLifeTime) {
            $output['transitionKey'] = $this->transitionKey = null;
        }

        if (empty($output['transitionKey'])) {
            $output['transitionKey'] = null;
        }

        return $output;
    }

    public function setUserId(?string $id)
    {
        $this->userId = $id;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }




    public function setStartTime($time)
    {
        if ($time !== null) {
            if ($time instanceof core\time\IDate) {
                $time = $time->toTimestamp();
            }

            $time = (int)$time;
        }

        $this->startTime = $time;
        return $this;
    }

    public function getStartTime()
    {
        return $this->startTime;
    }

    public function setAccessTime($time)
    {
        if ($time !== null) {
            if ($time instanceof core\time\IDate) {
                $time = $time->toTimestamp();
            }

            $time = (int)$time;
        }

        $this->accessTime = $time;
        return $this;
    }

    public function getAccessTime()
    {
        return $this->accessTime;
    }

    public function isAccessOlderThan($seconds)
    {
        return time() - $this->accessTime > $seconds;
    }

    public function setTransitionTime($time)
    {
        if ($time !== null) {
            if ($time instanceof core\time\IDate) {
                $time = $time->toTimestamp();
            }

            $time = (int)$time;
        }

        $this->transitionTime = $time;
        return $this;
    }

    public function getTransitionTime()
    {
        return $this->transitionTime;
    }


    public function toArray(): array
    {
        return $this->toDataRowArray();
    }

    public function toDataRowArray()
    {
        return [
            'id' => $this->id,
            'publicKey' => $this->publicKey,
            'transitionKey' => $this->transitionKey,
            'user' => $this->userId,
            'startTime' => $this->startTime,
            'accessTime' => $this->accessTime,
            'transitionTime' => $this->transitionTime
        ];
    }
}
