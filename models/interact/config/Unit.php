<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\interact\config;

use df;
use df\core;
use df\apex;
use df\axis;
use df\user;
    
class Unit extends axis\unit\config\Base {

    const ID = 'Interact';

    public function getDefaultValues() {
        return [
            'avatar' => [
                'adapter' => 'gravatar',
                'defaultImage' => null
            ],
            'comments' => [

            ]
        ];
    }

    public function setDefaultAvatarPath($path) {
        $this->values->avatar->defaultImage = $path;
        return $this;
    }

    public function getDefaultAvatarPath() {
        return $this->values->avatar['defaultImage'];
    }
}