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

use DecodeLabs\Atlas;
use DecodeLabs\Exceptional;

class Registry implements IRegistry
{
    use spur\TGuzzleMediator;

    const BASE_URL = 'https://registry.bower.io/';
    const TIMEOUT = '1 day';

    protected $_cachePath;

    public function __construct()
    {
        $this->_cachePath = '/tmp/decode-framework/bower/registry';
    }

    public function lookup($name)
    {
        $path = $this->_cachePath.'/'.$name.'.json';
        $timeout = core\time\Duration::factory(self::TIMEOUT)->getSeconds();

        if (is_file($path)) {
            if ((time() - filemtime($path) < $timeout)) {
                return flex\Json::fileToTree($path);
            } else {
                Atlas::$fs->deleteFile($path);
            }
        }

        try {
            $data = $this->requestJson('get', 'packages/'.rawurlencode($name));
        } catch (\Throwable $e) {
            throw Exceptional::Api([
                'message' => $e->getMessage(),
                'previous' => $e,
                'code' => $e->getCode()
            ]);
        }

        flex\Json::toFile($path, $data);

        return $data;
    }

    public function resolveUrl($name)
    {
        return $this->lookup($name)['url'];
    }


    // Server
    public function createUrl(string $path): link\http\IUrl
    {
        return link\http\Url::factory(self::BASE_URL.ltrim($path, '/'));
    }
}
