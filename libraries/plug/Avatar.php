<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\plug;

use DecodeLabs\Disciple;
use df\arch;
use df\core;

use df\link;

class Avatar implements arch\IDirectoryHelper
{
    use arch\TDirectoryHelper;

    public const GRAVATAR_BASE = '//www.gravatar.com/avatar/';

    public function getClientAvatarUrl($size = null)
    {
        return $this->getAvatarUrl(Disciple::getId(), $size);
    }

    public function getAvatarUrl($userId, $size = null)
    {
        return $this->context->uri->__invoke(
            'avatar/download?user=' . $userId . '&size=' . $size . '&c=' . $this->context->data->user->cache->getAvatarCacheTime()
        );
    }

    public function getGravatarUrl($email, $size = null, $default = 'mm')
    {
        $hash = md5(trim(strtolower((string)$email)));
        $output = new link\http\Url(self::GRAVATAR_BASE . $hash);

        if ($size !== null) {
            $output->query->s = (int)$size;
        }

        if ($url = $this->_getDefaultAvatarImageUrl()) {
            if (substr($url->getDomain(), -4) == '.dev' ||
                substr($url->getDomain(), -13) == '.localtest.me' ||
                $url->hasCredentials()) {
                $this->_defaultImageUrl = null;
            } else {
                if ($size !== null) {
                    $url = clone $url;
                    $url->path->setFilename('default-' . $size);
                }

                $default = $url;
            }
        }

        if ($default !== null) {
            $output->query->d = $default;
        }

        return $output;
    }

    private $_defaultImageUrl = false;

    protected function _getDefaultAvatarImageUrl()
    {
        if ($this->_defaultImageUrl === false) {
            $path = $this->context->data->user->avatarConfig->getDefaultAvatarPath();
            $this->_defaultImageUrl = null;

            if ($path) {
                $path = new core\uri\Path($path);
                $this->_defaultImageUrl = $this->context->uri->__invoke('avatar/download?user=default&type=' . $path->getExtension());

                $config = core\app\http\Config::getInstance();

                if ($credentials = $config->getCredentials()) {
                    $this->_defaultImageUrl->setCredentials(
                        $credentials['username'],
                        $credentials['password']
                    );
                }
            }
        }

        return $this->_defaultImageUrl;
    }
}
