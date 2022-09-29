<?php

require_once dirname(__DIR__).'/vendor/autoload.php';
require_once dirname(__DIR__).'/Df.Genesis.php';

use DecodeLabs\Genesis;
use DecodeLabs\R7\Genesis\Hub;

Genesis::initialize(Hub::class, [
    'analysis' => true
]);
