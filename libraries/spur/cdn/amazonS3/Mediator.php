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

    use spur\THttpMediator;
    
    const ENDPOINT = 's3.amazonaws.com';

    protected $_accessKey;
    protected $_secretKey;
    protected $_useSsl = false;

    public function __construct($accessKey, $secretKey, $useSsl=false) {
        //$this->getHttpClient(); // DELETE ME

        $this->setAccessKey($accessKey);
        $this->setSecretKey($secretKey);
        $this->shouldUseSsl((bool)$useSsl);
    }


// Client
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
    public function createBucket($name, $acl=IAcl::PRIVATE_READ_WRITE, $location=null) {
        $request = $this->createRequest('put', $name);
        $request->getHeaders()->set('x-amz-acl', $acl);

        if($location !== null) {
            $xml = new core\xml\Writer();
            $xml->startElement('CreateBucketConfiguration');
            $xml->writeElement('LocationConstraint', $location);
            $xml->endElement();
            $request->setBodyData($xml->toString());
            $request->getHeaders()->set('Content-Type', 'application/xml');
        }

        $this->sendRequest($request);
        return $this;
    }

    public function deleteBucket($name) {
        $this->requestRaw('delete', ['path' => '/', 'bucket' => $name]);
        return $this;
    }

    public function getBucketList() {
        $response = $this->requestXml('get', '/');
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

    public function getBucketLocation($bucket) {
        $response = $this->requestXml('get', ['path' => '/', 'bucket' => $bucket], ['location' => null]);
        return $response['xml']->getTextContent();
    }

    public function getBucketObjectList($bucket, $prefix=null, $limit=null, $marker=null) {
        $request = $this->createRequest('get', ['path' => '/', 'bucket' => $bucket]);
        $query = $request->getUrl()->getQuery();
        $fetchAll = true;

        if($limit !== null) {
            $fetchAll = false;
            $limit = (int)$limit;

            if($limit > 0) {
                $query->{'max-keys'} = $limit;
            } else {
                $limit = null;
            }
        }

        if($prefix !== null) {
            $query->prefix = (string)$prefix;
        }

        $output = [
            'bucket' => null,
            'isTruncated' => false,
            'limit' => $limit,
            'requests' => 0,
            'objects' => []
        ];

        do {
            if($marker !== null) {
                $query->marker = $marker;
            }

            $response = $this->_extractXml($this->sendRequest($request));
            $output['requests']++;
            $isTruncated = $response['xml']->getChildTextContent('IsTruncated') == 'true';
            $output['isTruncated'] = $isTruncated;

            if(!$output['bucket']) {
                $output['bucket'] = $response['xml']->getChildTextContent('Name');
            }

            if(!$fetchAll) {
                $output['limit'] = (int)$response['xml']->getChildTextContent('MaxKeys');
            }

            foreach($response['xml']->getChildrenOfType('Contents') as $contentNode) {
                $marker = $key = $contentNode->getChildTextContent('Key');

                $output['objects'][$key] = [
                    'name' => $key,
                    'modified' => new core\time\Date($contentNode->getChildTextContent('LastModified')),
                    'size' => $contentNode->getChildTextContent('Size'),
                    'hash' => substr($contentNode->getChildTextContent('ETag'), 1, -1)
                ];
            }
        } while($fetchAll && $isTruncated);

        return $output;
    }


// Objects
    public function getObjectInfo($bucket, $path) {
        $response = $this->requestRaw('head', ['path' => $path, 'bucket' => $bucket]);

        $headers = $response->getHeaders();
        $meta = [];

        foreach($headers as $key => $value) {
            $key = strtolower($key);

            if(substr($key, 0, 11) == 'x-amz-meta-') {
                $meta[substr($key, 11)] = $value;
            }
        }

        return [
            'name' => $path,
            'modified' => new core\time\Date($headers->get('Last-Modified')),
            'size' => $headers->get('Content-Length'),
            'hash' => substr($headers->get('Etag'), 1, -1),
            'type' => $headers->get('Content-Type'),
            'meta' => $meta
        ];
    }

    public function newUpload($bucket, $path, core\fs\IFile $file) {
        return new Upload($this, $bucket, $path, $file);
    }

    public function newCopy($fromBucket, $fromPath, $toBucket, $toPath) {
        return new Copy($this, $fromBucket, $fromPath, $toBucket, $toPath);
    }

    public function renameFile($bucket, $path, $newName, $acl=IAcl::PRIVATE_READ_WRITE) {
        $toPath = new core\uri\Path($path);
        $toPath->setBasename($newName);
        $toPath = $toPath->toString();

        return $this->moveFile($bucket, $path, $toPath, $acl);
    }

    public function moveFile($bucket, $fromPath, $toPath, $acl=IAcl::PRIVATE_READ_WRITE) {
        $this->newCopy($bucket, $fromPath, $bucket, $toPath)
            ->setAcl($acl)
            ->send();

        $this->deleteFile($bucket, $path);
        return $this;
    }

    public function deleteFile($bucket, $path) {
        $this->requestRaw('delete', ['path' => $path, 'bucket' => $bucket]);
        return $this;
    }

    public function deleteFolder($bucket, $path) {
        // TODO: send requests concurrently
        $path = rtrim($path, '/').'/';

        foreach($this->getBucketObjectList($bucket, $path)['objects'] as $name => $fileInfo) {
            $this->deleteFile($bucket, $name);
        }

        return $this;
    }


// IO
    public function requestXml($method, $path, array $data=[], array $headers=[]) {
        $response = $this->sendRequest($this->createRequest(
            $method, $path, $data, $headers
        ));

        return $this->_extractXml($response);
    }

    protected function _extractXml(link\http\IResponse $response) {
        return [
            'xml' => core\xml\Tree::fromXmlString($response->getContent()),
            'http' => $response
        ];
    }

    public function createUrl($path) {
        $bucket = null;

        if(isset($path['bucket'])) {
            $bucket = $path['bucket'];
            unset($path['bucket']);
            $path = array_shift($path);
        }

        $path = (string)$path;
        $url = self::ENDPOINT;
        $path = ltrim($path, '/');

        if($bucket !== null) {
            if($this->_isDnsBucketName($bucket)) {
                $url = $bucket.'.'.$url.'/'.$path;
            } else {
                $url = $url.'/'.$bucket.'/'.$path;
            }

            $resource = '/'.$bucket.'/'.$path;
        } else {
            $url .= '/'.$path;
            $resource = '/'.$path;
        }

        $url = link\http\Url::factory($url);
        $url->isSecure($this->_useSsl);
        $url->query->_resource = $resource;

        return $url;
    }

    protected function _prepareRequest(link\http\IRequest $request) {
        $request = clone $request;

        $headers = $request->getHeaders();
        $url = $request->getUrl();
        $resource = $url->query['_resource'];
        unset($url->query->_resource);

        if($url->query->hasAnyKey('acl', 'location', 'torrent', 'website', 'logging')) {
            $qString = $url->getQueryString();

            if(!empty($qString)) {
                $resource .= '?'.$qString;
            }
        }

        $amz = [];

        foreach($headers as $key => $value) {
            $key = strtolower($key);

            if(substr($key, 0, 6) == 'x-amz-') {
                $amz[] = $key.':'.$value;
            }
        }

        if(empty($amz)) {
            $amz = '';
        } else {
            usort($amz, function($a, $b)  {
                $lenA = strpos($a, ':');
                $lenB = strpos($b, ':');
                $minLength = min($lenA, $lenB);
                $ncmp = strncmp($a, $b, $minLength);

                if($lenA == $lenB) {
                    return $ncmp;
                }

                if(0 == $ncmp) {
                    return $lenA < $lenB ? -1 : 1;
                }

                return $ncmp;
            });

            $amz = "\n".implode("\n", $amz);
        }

        $headers->set('Date', $date = gmdate('D, d M Y H:i:s T'));
        $request->prepareHeaders();

        $headers->set('Authorization', $this->_createSignature(
            $request->getMethod()."\n".
            $headers->get('Content-Md5')."\n".
            $headers->get('Content-Type')."\n".
            $date.$amz."\n".
            $resource
        ));

        return $request;
    }

    protected function _extractResponseError(link\http\IResponse $response) {
        $content = $response->getContent();
        $xml = null;

        core\dump($response);

        if(strlen($content) && $response->getHeaders()->get('Content-Type') == 'application/xml') {
            $xml = core\xml\Tree::fromXmlString($content);
        }

        if($xml) {
            return new ApiException(
                $xml->Code[0]->getTextContent(),
                $xml->Message[0]->getTextContent(),
                $response->getHeaders()->getStatusCode(),
                $xml
            );
        } else if($response->getHeaders()->hasStatusCode(404)) {
            return new RuntimeException(
                'Resource not found - '.$request->getUrl()
            );
        } else {
            return new RuntimeException(
                'An unknown API error occurred'
            );
        }
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
        return 'AWS '.$this->_accessKey.':'.base64_encode(hash_hmac('sha1', $string, $this->_secretKey, true));
    }
}