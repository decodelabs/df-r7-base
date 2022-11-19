<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\media\_nodes;

use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskFixActiveVersions extends arch\node\Task
{
    public function execute(): void
    {
        Cli::{'yellow'}('Scanning files');

        $query = $this->data->media->file->select('id', 'fileName')
            ->where('activeVersion', '=', null)

            ->selectAttachRelation('versions', 'id')
                ->orderBy('isActive DESC', 'creationDate DESC')
                ->asList('versions', 'id');


        if (!$query->count()) {
            Cli::{'yellow'}(': ');
            Cli::success('none found');
        } else {
            Cli::newLine();
        }

        foreach ($query as $file) {
            Cli::{'brightMagenta'}($file['id'] . ' - ' . $file['fileName']);

            if (!isset($file['versions'][0])) {
                Cli::{'.brightYellow'}(' : SKIPPED');
                continue;
            }

            $id = $file['versions'][0];

            $this->data->media->file->update(['activeVersion' => $id])
                ->where('id', '=', $file['id'])
                ->execute();

            Cli::newLine();
        }
    }
}
