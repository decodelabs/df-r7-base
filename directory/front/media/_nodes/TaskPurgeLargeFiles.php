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

    const THRESHOLD = '2mb';

    public function execute() {
        if(!$this->app->isDevelopment()) {
            $this->io->writeErrorLine('This task cannot be run on production systems');
            return;
        }

        $handler = neon\mediaHandler\Base::getInstance();

        if(!$handler instanceof neon\mediaHandler\ILocalDataHandler) {
            $this->io->writeErrorLine('Media handler is not local');
        }

        $limit = $this->_getLimit();

        $this->io->writeLine('Purging large files...');
        $this->io->indent();

        $total = 0;

        $versions = $this->data->media->version->select('id', 'file', 'fileName', 'fileSize')
            ->where('purgeDate', '=', null)
            ->where('fileSize', '>', $limit);

        foreach($versions as $version) {
            $path = $handler->getFilePath($version['file'], $version['id']);

            if(!file_exists($path)) {
                continue;
            }

            $this->io->writeLine($version['fileName'].' - '.$this->format->fileSize($version['fileSize']));
            core\fs\File::delete($path);

            $total += $version['fileSize'];
        }

        $this->io->outdent();

        $this->io->writeLine('Purged '.$this->format->fileSize($total).' in total');
    }

    protected function _getLimit(): ?int {
        if(isset($this->request['limit'])) {
            $limit = core\unit\FileSize::normalize($this->request['limit']);
        } else {
            $limit = $this->_askFor('Size limit', function($answer) {
                return $this->data->newValidator()
                    ->addRequiredField('limit', 'fileSize')
                        ->setMin('1kb');
            }, self::THRESHOLD);
        }

        if($limit) {
            return $limit->getBytes();
        } else {
            return null;
        }
    }
}
