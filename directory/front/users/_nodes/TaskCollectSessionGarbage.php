<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\users\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

use DecodeLabs\Terminus\Cli;

class TaskCollectSessionGarbage extends arch\node\Task
{
    const LIFETIME = 86400; // 24 hours

    public function execute()
    {
        $time = time() - static::LIFETIME;

        while (true) {
            $descriptors = $this->data->session->descriptor->select('id')
                ->where('accessTime', '<', $time)
                ->limit(50)
                ->toArray();

            if (empty($descriptors)) {
                break;
            }

            $nodeCount = 0;

            foreach ($descriptors as $descriptor) {
                $nodeCount += $this->data->session->node->delete()
                    ->where('descriptor', '=', $descriptor['id'])
                    ->execute();

                $this->data->session->descriptor->delete()
                    ->where('id', '=', $descriptor['id'])
                    ->execute();

                usleep(500000);
            }


            Cli::operative($nodeCount.' nodes');
        }
    }
}
