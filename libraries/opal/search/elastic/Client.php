<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\search\elastic;

use df;
use df\core;
use df\opal;
use df\halo;

class Client implements opal\search\IClient {
    
    use opal\search\TClient;
    
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 9200;
    const DEFAULT_TIMEOUT = 300;
    
    protected $_servers = array();
    protected $_status = null;
        
    public static function factory($settings) {
        $settings = self::_normalizeSettings($settings);
        
        // TODO: cache output?
        
        return new self($settings);
    }
    
    protected function __construct(core\collection\ITree $settings) {
        if(isset($settings->servers)) {
            foreach($settings->servers as $server) {
                $this->_servers[] = [
                    'host' => $server->get('host', static::DEFAULT_HOST),
                    'port' => $server->get('port', static::DEFAULT_PORT)
                ];
            }
        } else {
            $this->_servers[] = [
                'host' => $settings->get('host', static::DEFAULT_HOST),
                'port' => $settings->get('port', static::DEFAULT_PORT)
            ];
        }
        
        if(empty($this->_servers)) {
            throw new opal\search\RuntimeException(
                'No valid elastic search servers have been specified'
            );
        }
    }
    
    public function getIndex($name) {
        return new Index($this, $name);
    }
    
    public function getIndexList() {
        $this->_fetchStatus();
        core\dump($this->_status);
    }
    
    
// Request
    public function sendRequest($uri, $data=null) {
        $uri = core\uri\Url::factory($uri);
        $method = strtoupper($uri->getScheme());
        
        switch($method) {
            case 'DELETE':
            case 'PUT':
            case 'POST':
                break;
                
            case 'GET':
            case '':
                $method = 'GET';
                break;
                
            default:
                throw new opal\search\RuntimeException(
                    'Unrecognized rest method: '.$method
                );
        }
        
        $request = new halo\protocol\http\request\Base($this->_servers[0]['host'].':'.$this->_servers[0]['port'].'/'.$uri->getPath());
        $client = new halo\protocol\http\Client($request);
        $client->run();
        
        core\dump($method, $uri->getPath(), $data);
    }
    
    protected function _fetchStatus() {
        if($this->_status !== null) {
            return;
        }
        
        $this->_status = $this->sendRequest('get://_status')->getData();
    }
}
