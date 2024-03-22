<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\session;

use DecodeLabs\Stash;
use DecodeLabs\Stash\Store\Generic as Store;

class Cache extends Store implements ICache
{
    public static function getInstance(): static
    {
        /** @var static $output */
        $output = Stash::load(__CLASS__);
        return $output;
    }

    public function insertDescriptor(Descriptor $descriptor)
    {
        $id = 'd.' . $descriptor->getPublicKeyHex();

        $justStarted = $descriptor->justStarted;
        $descriptor->justStarted = false;

        $this->set($id, $descriptor);

        $descriptor->justStarted = $justStarted;
        return $descriptor;
    }

    public function fetchDescriptor($publicKey)
    {
        return $this->get('d.' . bin2hex($publicKey));
    }

    public function removeDescriptor(Descriptor $descriptor)
    {
        $id = 'd.' . $descriptor->getPublicKeyHex();
        $this->delete($id);
    }


    public function fetchNode(IBucket $bucket, $key)
    {
        $id = 'i.' . $bucket->getDescriptor()->getIdHex() . '.' . md5($bucket->getName()) . '#' . $key;
        return $this->get($id);
    }

    public function insertNode(IBucket $bucket, Node $node)
    {
        $id = 'i.' . $bucket->getDescriptor()->getIdHex() . '.' . md5($bucket->getName()) . '#' . $node->key;
        $this->set($id, $node, 60 * 2);
        return $node;
    }

    public function removeNode(IBucket $bucket, $key)
    {
        $id = 'i.' . $bucket->getDescriptor()->getIdHex() . '.' . md5($bucket->getName()) . '#' . $key;
        $this->delete($id);
    }


    public function setGlobalKeyringTimestamp()
    {
        $this->set('m.globalKeyringTimestamp', time());
    }

    public function shouldRegenerateKeyring($keyringTimestamp)
    {
        if (!$keyringTimestamp) {
            return true;
        }

        $timestamp = $this->get('m.globalKeyringTimestamp');
        $output = false;

        if ($timestamp === null) {
            $this->setGlobalKeyringTimestamp();
            $output = true;
        } else {
            $output = $timestamp > $keyringTimestamp;
        }

        return $output;
    }
}
