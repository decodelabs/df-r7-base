<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\ctrl;

use df;
use df\core;
use df\ctrl;
    
class Manager implements core\IApplicationAware {

    use core\TApplicationAware;

    public function __construct(core\IApplication $application) {
        $this->_application = $application;
    }

    public function buildApp() {
        $builder = new ctrl\app\Builder(df\Launchpad::$loader);
        $builder->build();
    }

    public function initGitignore() {
        $path = df\Launchpad::$applicationPath;
        copy(__DIR__.'/default.gitignore', $path.'/.gitignore');
    }
}