<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache\backend;

use df;
use df\core;

class Memcached implements core\cache\IBackend {

    use core\TValueMap;

    protected $_connection;
    protected $_prefix;
    protected $_lifeTime;
    protected $_cache;

    public static function purgeApp(core\collection\ITree $options) {
        self::purgeAll($options);
    }

    public static function purgeAll(core\collection\ITree $options) {
        if(!self::isLoadable()) {
            return;
        }

        $connection = self::_loadConnection($options);
        $connection->flush();
    }

    public static function prune(core\collection\ITree $options) {
        // pruning is automatic :)
    }

    public static function isLoadable(): bool {
        return extension_loaded('memcached');
    }

    protected static function _loadConnection(core\collection\ITree $options) {
        $output = new \Memcached();

        if($options->has('servers')) {
            $serverList = $options->servers;
        } else {
            $serverList = [$options];
        }

        foreach($serverList as $serverOptions) {
            $output->addServer(
                $serverOptions->get('host', '127.0.0.1'),
                $serverOptions->get('port', 11211),
                (bool)$serverOptions->get('persistent', true)
            );
        }

        return $output;
    }

    public static function clearFor(core\collection\ITree $options, core\cache\ICache $cache) {
        if(self::isLoadable()) {
            (new self($cache, 0, $options))->clear();
        }
    }

    public function __construct(core\cache\ICache $cache, int $lifeTime, core\collection\ITree $options) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;
        $this->_prefix = df\Launchpad::$app->getUniquePrefix().'-'.$cache->getCacheId().':';

        $this->_connection = self::_loadConnection($options);
    }

    public function getConnectionDescription(): string {
        $servers = $this->_connection->getServerList();
        $output = [];

        foreach($servers as $server) {
            $output[] = $server['host'].':'.$server['port'];
        }

        return implode(' + ', $output);
    }

    public function getStats(): array {
        return $this->_connection->getStats();
    }

    public function setLifeTime(int $lifeTime) {
        $this->_lifeTime = $lifeTime;
        return $this;
    }

    public function getLifeTime(): int {
        return $this->_lifeTime;
    }


    public function set($key, $value, $lifeTime=null) {
        if($lifeTime === null) {
            $lifeTime = $this->_lifeTime;
        }

        return $this->_connection->set(
            $this->_prefix.$key,
            [serialize($value), time()],
            $lifeTime
        );
    }

    public function get($key, $default=null) {
        $val = $this->_connection->get($this->_prefix.$key);

        if(is_array($val)) {
            try {
                return unserialize($val[0]);
            } catch(\Throwable $e) {
                core\logException($e);
                return $default;
            }
        }

        return $default;
    }

    public function has(...$keys) {
        foreach($keys as $key) {
            if(is_array($this->_connection->get($this->_prefix.$key))) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys) {
        foreach($keys as $key) {
            $this->_connection->delete($this->_prefix.$key);
        }

        return true;
    }

    public function clear() {
        foreach($this->getKeys() as $key) {
            $this->remove($key);
        }

        return $this;
    }

    public function clearBegins(string $key) {
        foreach($this->getKeys() as $test) {
            if(0 === strpos($test, $key)) {
                $this->remove($test);
            }
        }

        return $this;
    }

    public function clearMatches(string $regex) {
        foreach($this->getKeys() as $test) {
            if(preg_match($regex, $test)) {
                $this->remove($test);
            }
        }

        return $this;
    }

    public function count() {
        $output = 0;
        $length = strlen($this->_prefix);

        foreach($this->_connection->getAllKeys() as $keys) {
            if(0 === strpos($key, $this->_prefix)) {
                $output++;
            }
        }

        return $output;
    }

    public function getKeys(): array {
        $output = [];
        $length = strlen($this->_prefix);

        foreach($this->_connection->getAllKeys() as $key) {
            if(0 === strpos($key, $this->_prefix)) {
                $output[] = substr($key, $length);
            }
        }

        return $output;
    }

    public function getCreationTime(string $key): ?int {
        $val = $this->_connection->get($this->_prefix.$key);

        if(is_array($val)) {
            return $val[1];
        }

        return null;
    }
}
