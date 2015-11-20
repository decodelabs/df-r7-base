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
use df\neon;

class TaskUpdateHashes extends arch\node\Task {

    public function execute() {
        $handler = $this->data->media->getMediaHandler();

        if(!$handler instanceof neon\mediaHandler\ILocalDataHandler) {
            $this->io->writeErrorLine('Media handler does not serve local data');
            return;
        }

        $list = $this->data->media->version->fetch()
            ->populate('file');

        if(!isset($this->request['all'])) {
            $list->where('hash', '=', null);
        }

        $count = $list->count();
        $this->io->writeLine('Fetching '.$count.' objects...');
        $count = 0;

        foreach($list as $version) {
            $timer = new core\time\Timer();

            $this->io->write(str_pad($version['fileName'].'... ', 50));
            $version->hash = $hash = $handler->hashFile(
                $version['file']['id'],
                $version['id'],
                (string)$version['id'] == (string)$version['file']['#activeVersion']
            );

            if($hash === null) {
                $this->io->writeLine('!! Skipped !!');
                continue;
            }

            $this->io->writeLine(bin2hex($hash).' ('.$timer.')');
            $version->save();
            $count++;
        }

        $this->io->writeLine('Finished hashing '.$count.' files');
    }
}