<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\plug;

use DecodeLabs\Disciple;
use df\arch;
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

        if ($default !== null) {
            $output->query->d = $default;
        }

        return $output;
    }
}
