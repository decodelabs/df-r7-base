<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\packaging\bower;

use df;
use df\core;
use df\spur;
use df\link;
use df\flex;

class Registry implements IRegistry {

    use spur\THttpMediator;

    const BASE_URL = 'https://bower.herokuapp.com/';
    const TIMEOUT = '1 day';

    protected $_cachePath;

    public function __construct() {
        $this->_cachePath = core\fs\Dir::getGlobalCachePath().'/bower/registry';
    }

    public function lookup($name) {
        $path = $this->_cachePath.'/'.$name.'.json';
        $timeout = core\time\Duration::factory(self::TIMEOUT)->getSeconds();

        if(is_file($path)) {
            if((time() - filemtime($path) < $timeout)) {
                return flex\Json::fileToTree($path);
            } else {
                core\fs\File::delete($path);
            }
        }

        try {
            $data = $this->requestJson('get', 'packages/'.rawurlencode($name));
        } catch(\Throwable $e) {
            throw core\Error::EApi([
                'message' => $e->getMessage(),
                'previous' => $e,
                'code' => $e->getCode()
            ]);
        }

        flex\Json::toFile($path, $data);

        return $data;
    }

    public function resolveUrl($name) {
        return $this->lookup($name)['url'];
    }


// Server
    public function createUrl($path) {
        return link\http\Url::factory(self::BASE_URL.ltrim($path, '/'));
    }
}