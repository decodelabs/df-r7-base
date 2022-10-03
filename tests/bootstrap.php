<?php

require_once 'vendor/autoload.php';
require_once 'vendor/decodelabs/genesis/src/bootstrap.php';

use DecodeLabs\Genesis;
use DecodeLabs\R7\Genesis\Hub;

Genesis::initialize(Hub::class, [
    'analysis' => true
]);
