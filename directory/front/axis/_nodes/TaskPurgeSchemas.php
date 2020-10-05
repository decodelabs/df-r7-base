<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\axis\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;
use df\opal;

use DecodeLabs\Terminus as Cli;

class TaskPurgeSchemas extends arch\node\Task
{
    public function execute()
    {
        Cli::{'yellow'}('Purging schemas: ');
        $this->data->axis->schema->getUnitAdapter()->getQuerySourceAdapter()->drop();
        $this->data->axis->getSchemaManager()->clearCache();
        Cli::success(' done');
    }
}
