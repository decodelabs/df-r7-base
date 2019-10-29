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
use df\spur;

use DecodeLabs\Terminus\Cli;

class TaskInit extends arch\node\Task
{
    const GEOMETRY = '1914x1036+5+23 450 300';

    public function execute()
    {
        $this->ensureDfSource();

        $path = df\Launchpad::$app->path;
        $this->runChild('git/init-gitignore');

        if (is_dir($path.'/.git')) {
            Cli::success('App repository has already been initialized');
            $repo = new spur\vcs\git\Repository($path);
        } else {
            Cli::{'yellow'}('Initialising git repository: ');
            $repo = spur\vcs\git\Repository::createNew($path);
            Cli::success('done');
        }

        if ($repo->getConfig('core.filemode')) {
            Cli::{'yellow'}('Turning off file mode: ');
            $repo->setConfig('core.filemode', false);
            Cli::success('done');
        }

        if ($repo->getConfig('gui.geometry') != self::GEOMETRY) {
            if (Cli::confirm('Would you like to set default GUI config @1020p?', true)->prompt()) {
                Cli::{'yellow'}('Setting geometry: ');

                $repo->setConfig('gui.wmstate', 'zoomed');
                $repo->setConfig('gui.geometry', self::GEOMETRY);
                Cli::success(self::GEOMETRY);
            }
        }

        $push = false;

        if (!$repo->countCommits()) {
            Cli::{'yellow'}('Making initial commit: ');
            $repo->commitAllChanges('Initial commit');
            Cli::success('done');
            $push = true;
        }

        if (!$repo->countRemotes()) {
            Cli::newLine();
            Cli::{'.cyan'}('Please enter remote origin: ');
            Cli::write('> ');
            $origin = trim(Cli::readLine());

            if (!preg_match('/^(http(s)?|git\@)/i', $origin)) {
                Cli::error('This doesn\'t look like a valid remote url');
                $push = false;
            } else {
                $repo->addRemote('origin', $origin);
            }
        }

        if ($push) {
            Cli::{'yellow'}('Pushing initial commit: ');
            $repo->pushUpstream();
            Cli::success('done');
        }
    }
}
