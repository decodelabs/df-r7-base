<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\response;

use df;
use df\core;
use df\link;

class HeaderCollection extends core\collection\HeaderMap implements link\http\IResponseHeaderCollection {

    use link\http\THeaderCollection;

    protected static $_messages = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded'
    ];

    protected $_statusCode = 200;
    protected $_statusMessage = null;

    public static function isValidStatusCode($code) {
        return isset(self::$_messages[$code]);
    }

    public static function statusCodeToString($code) {
        if(self::isValidStatusCode($code)) {
            return $code.' '.self::$_messages[$code];
        } else {
            return $code;
        }
    }

    public static function statusCodeToMessage($code) {
        if(self::isValidStatusCode($code)) {
            return self::$_messages[$code];
        } else {
            return $code;
        }
    }

    private static function _getStatusCodeCategory($code) {
        if(self::isValidStatusCode($code)) {
            return substr($code,0,1);
        } else {
            return false;
        }
    }

    public static function isInformationStatusCode($code) {
        return self::_getStatusCodeCategory($code) == 1;
    }

    public static function isSuccessStatusCode($code) {
        return self::_getStatusCodeCategory($code) == 2;
    }

    public static function isRedirectStatusCode($code) {
        return self::_getStatusCodeCategory($code) == 3;
    }

    public static function isClientErrorStatusCode($code) {
        return self::_getStatusCodeCategory($code) == 4;
    }

    public static function isServerErrorStatusCode($code) {
        return self::_getStatusCodeCategory($code) == 5;
    }

    public static function isErrorStatusCode($code) {
        $cat = self::_getStatusCodeCategory($code);
        return $cat == 4 || $cat == 5;
    }


    public static function fromResponseString($string, &$content=null) {
        $parts = preg_split('|(?:\r?\n){2}|m', $string, 2);
        $headers = array_shift($parts);
        $content = array_shift($parts);
        return self::fromResponseArray(explode("\n", $headers));
    }

    public static function fromResponseArray(array $lines) {
        $output = new self();
        $http = array_shift($lines);

        if(!preg_match("|^HTTP/([\d\.x]+) (\d+) ([^\r\n]+)|", $http, $matches)) {
            throw new link\http\UnexpectedValueException(
                'Headers do not appear to be valid HTTP format'
            );
        }

        $output->setHttpVersion($matches[1]);
        $output->setStatusCode($matches[2]);
        $output->setStatusMessage($matches[3]);

        foreach($lines as $line) {
            $output->add(trim(strtok(trim($line), ':')), trim(strtok('')));
        }

        return $output;
    }



// Status
    public function setStatusCode($code) {
        if(!self::isValidStatusCode($code)) {
            throw new link\http\InvalidArgumentException(
                $code.' is not a valid http response code'
            );
        }

        $this->_statusCode = (int)$code;
        return $this;
    }

    public function getStatusCode() {
        return $this->_statusCode;
    }

    public function hasErrorStatusCode() {
        return $this->_statusCode >= 400;
    }

    public function hasSuccessStatusCode() {
        return $this->_statusCode < 300;
    }

    public function hasRedirectStatusCode() {
        return $this->_statusCode >= 300 && $this->_statusCode < 400;
    }

    public function hasStatusCode($code) {
        return in_array($this->_statusCode, func_get_args());
    }

    public function setStatusMessage($message) {
        $this->_statusMessage = $message;
        return $this;
    }

    public function getStatusMessage() {
        if($this->_statusMessage) {
            return $this->_statusMessage;
        }

        if(isset(self::$_messages[$this->_statusCode])) {
            return self::$_messages[$this->_statusCode];
        }

        return null;
    }


// Cache
    public function getCacheControl() {
        $output = $this->get('cache-control');

        if(!$output instanceof CacheControl) {
            $output = new CacheControl($output);
            $this->set('cache-control', $output);
        }

        return $output;
    }

    public function setCacheAccess($access='private') {
        if(is_string($access)) {
            $access = strtolower($access);
        } else if($access === true) {
            $access = 'public';
        }

        $cacheControl = $this->getCacheControl();

        switch($access) {
            case 'public':
            case 'private':
            case null:
            case false:
                $cacheControl->setAccess($access);
                $this->remove('pragma');
                break;

            case 'no-cache':
                $cacheControl->setAccess($access);
                $this->set('pragma', 'no-cache');
                break;

            default:
                break;
        }

        return $this;
    }

    public function getCacheAccess() {
        return $this->getCacheControl()->getAccess();
    }

    public function canStoreCache($flag=null) {
        $cacheControl = $this->getCacheControl();

        if($flag !== null) {
            $cacheControl->canStore((bool)$flag);
            return $this;
        }

        return $cacheControl->canStore();
    }

    public function canTransformCache($flag=null) {
        $cacheControl = $this->getCacheControl();

        if($flag !== null) {
            $cacheControl->canTransform((bool)$flag);
            return $this;
        }

        return $cacheControl->canTransform();
    }

    public function shouldRevalidateCache($flag=null) {
        $cacheControl = $this->getCacheControl();

        if($flag !== null) {
            $cacheControl->shouldRevalidate((bool)$flag);
            return $this;
        }

        return $cacheControl->shouldRevalidate();
    }

    public function shouldRevalidateProxyCache($flag=null) {
        $cacheControl = $this->getCacheControl();

        if($flag !== null) {
            $cacheControl->shouldRevalidateProxy((bool)$flag);
            return $this;
        }

        return $cacheControl->shouldRevalidateProxy();
    }

    public function setCacheExpiration($duration=null) {
        $now = new core\time\Date();

        if(is_int($duration)) {
            $then = $now->modify('+'.$duration.' seconds');
        } else if($duration !== null) {
            $then = core\time\Date::factory($duration);
            $duration = $then->toTimestamp() - $now->toTimestamp();
        } else {
            $then = $now;
        }

        if($duration < 1) {
            $duration = null;
        }

        $this->getCacheControl()->setExpiration($duration);

        if($duration !== null) {
            $this->set('expires', $then);
        } else {
            $this->remove('expires');
        }

        return $this;
    }

    public function getCacheExpiration() {
        return $this->getCacheControl()->getExpiration();
    }

    public function setSharedCacheExpiration($duration=null) {
        if(!is_int($duration) && $duration !== null) {
            $now = new core\time\Date();
            $then = core\time\Date::factory($duration);
            $duration = $then->toTimestamp() - $now->toTimestamp();
        }

        if($duration < 1) {
            $duration = null;
        }

        $this->getCacheControl()->setSharedExpiration($duration);
        return $this;
    }

    public function getSharedCacheExpiration() {
        return $this->getCacheControl()->getSharedExpiration();
    }

    public function getCacheStartDate(link\http\IRequest $request) {
        $headers = $request->getHeaders();

        if(!$request->isPost() && $headers->has('if-modified-since')) {
            $parts = explode(';', $headers->get('if-modified-since'));
            return new core\time\Date(current($parts));
        }

        return new core\time\Date();
    }

    public function isCached(link\http\IRequest $request) {
        if(!$request->isCachedByClient()) {
            return false;
        }

        if(!$lastModified = $this->get('last-modified')) {
            return false;
        }

        $lastModified = core\time\Date::factory($lastModified)->toTimestamp();
        return $this->getCacheStartDate($request)->toTimestamp() >= $lastModified;
    }


// Disposition
    public function setFileName($fileName, $isAttachment=null) {
        if($fileName === null) {
            $this->remove('content-disposition');
        } else {
            if($isAttachment === null) {
                $isAttachment = $this->isAttachment();
            }

            $this->set('content-disposition', ($isAttachment ? 'attachment' : 'inline').'; filename="'.$fileName.'"');
        }

        return $this;
    }

    public function getFileName() {
        if(!$this->has('content-disposition')) {
            return null;
        }

        return $this->getNamedValue('content-disposition', 'filename');
    }

    public function isAttachment($flag=null) {
        if($flag !== null) {
            if(!$flag) {
                $this->set('content-disposition', 'inline');
            } else {
                if(null === ($fileName = $this->getFileName())) {
                    $fileName = uniqid('download-');
                }

                $this->setFileName($fileName, true);
            }

            return $this;
        }

        return strtolower($this->getBase('content-disposition')) == 'attachment';
    }

    public function setAttachmentFileName($fileName) {
        return $this->setFileName($fileName, true);
    }

    public function getAttachmentFileName() {
        return $this->getFileName();
    }




// Send
    public function send() {
        header('HTTP/'.$this->getHttpVersion().' '.$this->getStatusCode().' '.$this->getStatusMessage());

        foreach($this->getLines() as $line) {
            header($line, false);
        }

        return $this;
    }

    public function getDumpProperties() {
        $output = parent::getDumpProperties();

        array_unshift($output,
            new core\debug\dumper\Property(
                'httpVersion', $this->_httpVersion, 'private'
            ),
            new core\debug\dumper\Property(
                'statusCode', $this->_statusCode, 'private'
            )
        );

        return $output;
    }
}