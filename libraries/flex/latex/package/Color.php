<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\package;

use df;
use df\core;
use df\flex;
use df\iris;

class Color extends Base {

    const COMMANDS = [
        'color', 'textcolor', 'definecolor', 'pagecolor', 'colorbox', 'fcolorbox'
    ];
}