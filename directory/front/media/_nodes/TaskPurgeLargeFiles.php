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

class TaskPurgeLargeFiles extends arch\node\Task {

    const THRESHOLD = 2621440; // 2.5mb

    public function execute() {
        if(!$this->app->isDevelopment()) {
            $this->io->writeErrorLine('This task cannot be run on production systems');
            return;
        }

        $handler = neon\mediaHandler\Base::getInstance();

        if(!$handler instanceof neon\mediaHandler\ILocalDataHandler) {
            $this->io->writeErrorLine('Media handler is not local');
        }

        $this->io->write('Purging large files...');
        $this->io->indent();

        $total = 0;

        $versions = $this->data->media->version->select('id', 'file', 'fileName', 'fileSize')
            ->where('purgeDate', '=', null)
            ->where('fileSize', '>', self::THRESHOLD);

        foreach($versions as $version) {
            $this->io->writeLine($version['fileName'].' - '.$this->format->fileSize($version['fileSize']));
            core\fs\File::delete($handler->getFilePath($version['file'], $version['id']));

            $total += $version['fileSize'];
        }

        $this->io->outdent();

        $this->io->writeLine('Purged '.$this->format->fileSize($total).' in total');
    }
}
