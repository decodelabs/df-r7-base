<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\media\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskFixActiveVersions extends arch\node\Task {

    public function execute() {
        $this->io->write('Scanning files...');

        $query = $this->data->media->file->select('id', 'fileName')
            ->where('activeVersion', '=', null)

            ->selectAttachRelation('versions', 'id')
                ->orderBy('isActive DESC', 'creationDate DESC')
                ->asList('versions', 'id');


        if(!$query->count()) {
            $this->io->writeLine(' none found');
        } else {
            $this->io->writeLine();
        }

        foreach($query as $file) {
            $this->io->write($file['id'].' - '.$file['fileName']);

            if(!isset($file['versions'][0])) {
                $this->io->writeLine(' : SKIPPED');
                continue;
            }

            $id = $file['versions'][0];

            $this->data->media->file->update(['activeVersion' => $id])
                ->where('id', '=', $file['id'])
                ->execute();

            $this->io->writeLine();
        }
    }
}