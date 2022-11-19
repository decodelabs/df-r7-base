<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\cache;

use df\axis;

class Unit extends axis\unit\Cache
{
    public function setAvatarCacheTime()
    {
        $this->set('avatarCacheTime', $time = time());
        return $time;
    }

    public function getAvatarCacheTime()
    {
        $time = $this->get('avatarCacheTime');

        if (!$time) {
            $time = $this->setAvatarCacheTime();
        }

        return $time;
    }
}
