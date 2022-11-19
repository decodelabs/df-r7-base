<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\axis\_nodes;

use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskPurgeSchemas extends arch\node\Task
{
    public function execute(): void
    {
        Cli::{'yellow'}('Purging schemas: ');
        $this->data->axis->schema->getUnitAdapter()->getQuerySourceAdapter()->drop();
        $this->data->axis->getSchemaManager()->clearCache();
        Cli::success(' done');
    }
}
