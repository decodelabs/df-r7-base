<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\theme\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\aura;
use df\spur;

class TaskInstallDependencies extends arch\task\Action {

    public function execute() {
        $this->io->write('Installing theme dependencies...');

        $config = aura\theme\Config::getInstance();
        $themes = array_unique($config->getThemeMap());
        $dependencies = [];

        foreach($themes as $themeId) {
            $theme = aura\theme\Base::factory($themeId);
            
            foreach($theme->getDependencies() as $name => $source) {
                $package = new spur\packaging\bower\Package($name, $source);

                if(isset($dependencies[$name]) && $dependencies[$name]->source != $package->source) {
                    $this->io->writeLine();
                    core\dump($dependencies[$name], $package);
                    $this->io->writeErrorLine('Version conflict for '.$name);
                }

                $dependencies[$package->name] = $package;
            }
        }

        if(empty($dependencies)) {
            $this->io->writeLine(' none found');
            return;
        }

        $this->io->writeLine();

        $installer = new spur\packaging\bower\Installer($this->io);
        $installer->installPackages($dependencies);
    }
}