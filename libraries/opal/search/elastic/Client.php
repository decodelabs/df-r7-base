<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\search\elastic;

use df;
use df\core;
use df\opal;
use df\link;
use df\flex;

class Client implements opal\search\IClient {
    
    use opal\search\TClient;
    
    const DEFAULT_HOST = 'localhost';
    const DEFAULT_PORT = 9200;
    const DEFAULT_TIMEOUT = 300;
    
    protected $_servers = [];
    protected $_status = null;
        
    public static function factory($settings) {
        $settings = self::_normalizeSettings($settings);
        
        // TODO: cache output?
        
        return new self($settings);
    }
    
    protected function __construct(core\collection\ITree $settings) {
        if(isset($settings->servers)) {
            foreach($settings->servers as $server) {
                $this->_servers[] = $this->_extractNodeSettings($server);
            }
        } else {
            $this->_servers[] = $this->_extractNodeSettings($settings);
        }
        
        if(empty($this->_servers)) {
            throw new opal\search\RuntimeException(
                'No valid elastic search servers have been specified'
            );
        }
    }
    
    private function _extractNodeSettings(core\collection\ITree $node) {
        return [
            'host' => $node->get('host', static::DEFAULT_HOST),
            'port' => $node->get('port', static::DEFAULT_PORT)
        ];
    }
    
    public function getIndex($name) {
        return new Index($this, $name);
    }
    
    public function getIndexList() {
        return $this->getStatus()->indices->getKeys();
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
        
        $request = new link\http\request\Base(
            $this->_servers[0]['host'].':'.$this->_servers[0]['port'].'/'.$uri->getPath()
        );
        
        $request->getUrl()->setQuery($uri->getQuery());
        $request->setMethod($method);
        $request->setBodyData($data);
        
        $response = null;
        
        new link\http\Client($request, function($httpResponse) use (&$response) {
            $message = null;
            
            try {
                $response = $httpResponse->getJsonContent();
            } catch(\Exception $e) {
                $message = $httpResponse->getContent();
            }
            
            if(!$httpResponse->isOk()) {
                if($message === null) {
                    $message = $response->get('error', $httpResponse->getHeaders()->getStatusMessage());
                }
                
                throw new opal\search\RuntimeException(
                    'Could not successfully contact elastic search server: '.$message
                );
            }
        });
        
        if(isset($response->error)) {
            throw new opal\search\RuntimeException(
                'Elastic response error: '.$response['error']
            );
        }
        
        return $response;
    }

    public function sendBulkRequest(array $data) {
        if(empty($data)) {
            return null;
        }
        
        $dataString = '';
        
        foreach($data as $actionSet) {
            $dataString .= flex\json\Codec::encode($actionSet)."\n";
        }
        
        return $this->sendRequest('put://_bulk', $dataString);
    }
    
    public function getStatus() {
        if($this->_status === null) {
            $this->_status = $this->sendRequest('get://_status');
        }
        
        return $this->_status;
    }
}
