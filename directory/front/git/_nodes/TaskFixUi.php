<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\git\_nodes;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;

use DecodeLabs\Terminus as Cli;

class TaskFixUi extends arch\node\Task
{
    const GEOMETRY = '1914x1036+5+23 450 300';

    public function execute()
    {
        $this->ensureDfSource();

        Cli::{'yellow'}('Updating repositories:');
        $model = $this->data->getModel('package');

        foreach ($model->getInstalledPackageList() as $package) {
            if (!$repo = $package['repo']) {
                continue;
            }

            if ($repo->getConfig('gui.geometry') != self::GEOMETRY) {
                Cli::{'brightMagenta'}(' '.$package['name']);

                $repo->setConfig('gui.wmstate', 'zoomed');
                $repo->setConfig('gui.geometry', self::GEOMETRY);
            }
        }

        Cli::newLine();
    }
}
