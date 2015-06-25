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
        $this->_cachePath = core\io\Util::getGlobalCachePath().'/bower/registry';
    }

    public function lookup($name) {
        $path = $this->_cachePath.'/'.$name.'.json';
        $timeout = core\time\Duration::factory(self::TIMEOUT)->getSeconds();

        if(is_file($path)) {
            if((time() - filemtime($path) < $timeout)) {
                return flex\json\Codec::decode(file_get_contents($path));
            } else {
                core\io\Util::deleteFile($path);
            }
        }

        $data = $this->callServer('get', 'packages/'.rawurlencode($name));
        core\io\Util::writeFileExclusive($path, flex\json\Codec::encode($data));
        return $data;
    }

    public function resolveUrl($name) {
        return $this->lookup($name)['url'];
    }
    

// Server
    protected function _createUrl($path) {
        return self::BASE_URL.ltrim($path, '/');
    }
}