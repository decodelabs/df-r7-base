<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\mail\_nodes;

use df\arch;
use df\flow;

use DecodeLabs\Terminus as Cli;

class TaskRefreshListSources extends arch\node\Task
{
    public const SCHEDULE = '0 4 * * *';
    public const SCHEDULE_AUTOMATIC = true;

    public function execute(): void
    {
        $sources = flow\Manager::getInstance()->getListSources();

        foreach ($sources as $source) {
            Cli::{'brightMagenta'}($source->getId().' ');
            Cli::{'brightYellow'}($source->getPrimaryListId().' ');

            $source->refetchManifest();

            Cli::success('done');
        }
    }
}
