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

use DecodeLabs\Atlas;
use DecodeLabs\Dictum;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

class TaskPurgeLargeFiles extends arch\node\Task
{
    public const THRESHOLD = '2mb';

    public function execute(): void
    {
        if (!Genesis::$environment->isDevelopment()) {
            Cli::error('This task cannot be run on production systems');
            return;
        }

        $handler = neon\mediaHandler\Base::getInstance();

        if (!$handler instanceof neon\mediaHandler\ILocalDataHandler) {
            Cli::error('Media handler is not local');
            return;
        }

        $limit = $this->_getLimit();
        $total = 0;

        $versions = $this->data->media->version->select('id', 'file', 'fileName', 'fileSize')
            ->where('purgeDate', '=', null)
            ->where('fileSize', '>', $limit);

        foreach ($versions as $version) {
            $path = $handler->getFilePath($version['file'], $version['id']);

            if (!file_exists($path)) {
                continue;
            }

            Cli::{'.brightMagenta'}($version['fileName'].' - '.Dictum::$number->fileSize($version['fileSize']));
            Atlas::deleteFile($path);

            $total += $version['fileSize'];
        }

        Cli::success('Purged '.Dictum::$number->fileSize($total).' in total');
    }

    protected function _getLimit(): ?int
    {
        if (isset($this->request['limit'])) {
            $limit = core\unit\FileSize::normalize($this->request['limit']);
        } else {
            $limit = $this->_askFor('Size limit', function ($answer) {
                return $this->data->newValidator()
                    ->addRequiredField('limit', 'fileSize')
                        ->setMin('1kb');
            }, self::THRESHOLD);
        }

        if ($limit) {
            return $limit->getBytes();
        } else {
            return null;
        }
    }
}
