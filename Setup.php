<?php
require_once 'Df.php';

if(!is_dir(__DIR__.'/setup')) {
    mkdir(__DIR__.'/setup/entry', 0770, true);
}

if(!isset($_SERVER['argv'][1])) {
    $_SERVER['argv'][1] = 'df/setup';
}

df\Launchpad::runAs('df', __DIR__.'/setup');
