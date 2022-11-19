<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\avatarConfig;

use df\axis;

class Unit extends axis\unit\Config
{
    public const ID = 'Avatars';

    public function getDefaultValues(): array
    {
        return [
            'adapter' => 'gravatar',
            'defaultImage' => null
        ];
    }

    public function setDefaultAvatarPath($path)
    {
        $this->values->defaultImage = $path;
        return $this;
    }

    public function getDefaultAvatarPath()
    {
        return $this->values['defaultImage'];
    }
}
