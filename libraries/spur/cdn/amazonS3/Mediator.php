<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\cdn\amazonS3;

use df;
use df\core;
use df\spur;
use df\link;

class Mediator implements IMediator {
    
    const ENDPOINT = 's3.amazonaws.com';

    protected $_httpClient;
    protected $_accessKey;
    protected $_secretKey;
    protected $_useSsl = false;

    public function __construct($accessKey, $secretKey, $useSsl=false) {
        $this->_httpClient = new link\http\Client();
        $this->setAccessKey($accessKey);
        $this->setSecretKey($secretKey);
        $this->shouldUseSsl((bool)$useSsl);
    }


// Client
    public function getHttpClient() {
        return $this->_httpClient;
    }

    public function setAccessKey($key) {
        $this->_accessKey = $key;
        return $this;
    }

    public function getAccessKey() {
        return $this->_accessKey;
    }

    public function setSecretKey($key) {
        $this->_secretKey = $key;
        return $this;
    }

    public function getSecretKey() {
        return  $this->_secretKey;
    }

    public function shouldUseSsl($flag=null) {
        if($flag !== null) {
            $this->_useSsl = (bool)$flag;
            return $this;
        }

        return $this->_useSsl;
    }

// Buckets
    public function getBucketList() {
        $response = $this->callServer($this->_newRequest('get', '/'));
        $output = [];

        $owner = $response['xml']->getFirstChildOfType('Owner');

        if($owner) {
            $ownerId = $owner->getChildTextContent('ID');
            $ownerName = $owner->getChildTextContent('DisplayName');
        } else {
            $ownerId = $ownerName = null;
        }

        foreach($response['xml']->Buckets[0]->Bucket as $bucketNode) {
            $output[] = [
                'name' => $bucketNode->getChildTextContent('Name'),
                'created' => new core\time\Date($bucketNode->getChildTextContent('CreationDate')),
                'ownerId' => $ownerId,
                'ownerName' => $ownerName
            ];
        }

        return $output;
    }



// IO
    public function callServer(link\http\IRequest $request) {
        $headers = $request->getHeaders();
        $amz = '';

        $headers->set('Date', gmdate('D, d M Y H:i:s T'));

        $headers->set('Authorization', $this->_createSignature(
            $request->getMethod()."\n".
            $headers->get('Content-MD5')."\n".
            $headers->get('Content-Type')."\n".
            $headers->get('Date').$amz."\n".
            $request->getUrl()->getLocalString()
        ));

        $response = $this->_httpClient->sendRequest($request);
        $xml = core\xml\Tree::fromXmlString($response->getContent());

        if(!$response->isOk()) {
            throw new ApiException(
                $xml->Code[0]->getTextContent(),
                $xml->Message[0]->getTextContent(),
                $response->getHeaders()->getStatusCode()
            );
        }

        return [
            'xml' => $xml,
            'http' => $response
        ];
    }

    protected function _newRequest($method, $path, $bucket=null) {
        $url = self::ENDPOINT;

        if($bucket !== null) {
            if($this->_isDnsBucketName($bucket)) {
                $url = $bucket.'.'.$url.'/'.ltrim($path, '/');
            } else {
                $url = $url.'/'.$bucket.'/'.ltrim($path, '/');
            }
        }

        $request = new link\http\request\Base($url);
        $request->getUrl()->isSecure($this->_useSsl);

        return $request;
    }

    protected function _isDnsBucketName($bucket) {
        if(strlen($bucket) > 63 || preg_match("/[^a-z0-9\.-]/", $bucket) > 0) {
            return false;
        }

        if(strstr($bucket, '-.') !== false || strstr($bucket, '..') !== false) {
            return false;
        }

        if(!preg_match("/^[0-9a-z]/", $bucket) || !preg_match("/[0-9a-z]$/", $bucket)) {
            return false;
        }

        return true;
    }

    protected function _createSignature($string) {
        return 'AWS '.$this->_accessKey.':'.$this->_createHash($string);
    }

    protected function _createHash($string) {
        return base64_encode(hash_hmac('sha1', $string, $this->_secretKey, true));
    }
}