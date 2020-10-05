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

use DecodeLabs\Terminus as Cli;

class TaskUpdateHashes extends arch\node\Task
{
    public function execute()
    {
        $handler = $this->data->media->getMediaHandler();

        if (!$handler instanceof neon\mediaHandler\ILocalDataHandler) {
            Cli::error('Media handler does not serve local data');
            return;
        }

        $list = $this->data->media->version->fetch()
            ->populate('file');

        if (!isset($this->request['all'])) {
            $list->where('hash', '=', null);
        }

        Cli::{'yellow'}('Fetching objects: ');
        $count = $list->count();
        Cli::success($count.' found');
        $count = 0;

        foreach ($list as $version) {
            $timer = new core\time\Timer();

            Cli::{'brightMagenta'}(str_pad($version['fileName'].': ', 45));

            $version->hash = $hash = $handler->hashFile(
                $version['file']['id'],
                $version['id'],
                (string)$version['id'] == (string)$version['file']['#activeVersion']
            );

            if ($hash === null) {
                Cli::operative('SKIPPED');
                continue;
            }

            Cli::success(bin2hex($hash).' ('.$timer.')');
            $version->save();
            $count++;
        }

        if ($count) {
            Cli::success('Finished hashing '.$count.' files');
        }
    }
}
