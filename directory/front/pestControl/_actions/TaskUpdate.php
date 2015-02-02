<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\pestControl\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskUpdate extends arch\task\Action {
    
    public function execute() {
        $this->io->writeLine('Rebuilding tables...');

        $tables = [
            'pestControl/accessLog', 'pestControl/error', 'pestControl/errorLog', 
            'pestControl/miss', 'pestControl/missLog', 'user/agent'
        ];

        foreach($tables as $table) {
            $this->runChild('axis/rebuild-table?unit='.$table);
            $this->runChild('axis/purge-table-backups?unit='.$table);
        }


        $this->io->write("\n".'Updating request fields...');
        $tables = ['accessLog', 'errorLog', 'miss', 'missLog'];
        $router = core\application\http\Router::getInstance();

        foreach($tables as $table) {
            $query = $this->data->pestControl->{$table}->fetch();
            $count = 0;

            foreach($query as $row) {
                $request = $row['request'];

                if(preg_match('/^[a-z]+\:\/\//i', $request)) {
                    $url = $this->uri($request);
                    $url = new core\uri\Url($url->getLocalString());
                    $router->mapPath($url->path);
                    $url = ltrim((string)$url, '/');

                    if(!strlen($url)) {
                        $url = '/';
                    }

                    $row['request'] = $url;
                    $row->save();
                    $count++;
                } else if(substr($request, 0, 1) == '/' && strlen($request) > 1) {
                    $row['request'] = ltrim($request, '/');
                    $row->save();
                    $count++;
                }
            }

            $this->io->write(' '.$table.'('.$count.')');
        }

        $this->io->write("\n".'Updating user agents...');
        $count = 0;

        foreach($this->data->user->agent->fetch() as $agent) {
            $agent['isBot'] = $this->data->user->agent->isBot($agent['body']);

            if($agent->hasChanged('isBot')) {
                $agent->save();
                $count++;
            }
        }

        $this->io->writeLine(' '.$count.' marked as bots');
        $this->io->write('Updating bot counts...');
        $count = 0;

        foreach($this->data->pestControl->miss->fetch() as $miss) {
            $miss['botsSeen'] = $miss->missLogs->select('id')
                ->joinRelation('userAgent', 'isBot')
                ->where('isBot', '=', true)
                ->count();

            if($miss->hasChanged()) {
                $miss->save();
                $count++;
            }
        }

        $this->io->writeLine(' '.$count.' found');
    }
}