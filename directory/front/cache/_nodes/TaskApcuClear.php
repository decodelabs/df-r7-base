<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\cache\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\link;

class TaskApcuClear extends arch\node\Task {

    use TApcuClear;

    const DEFAULT_ACCESS = arch\IAccess::ALL;
    const OPTIMIZE = true;

    public function execute() {
        $mode = $this->request->query->get('mode');
        unset($this->request->query->mode);
        $isHttp = $isCli = true;

        if($mode) {
            $mode = strtolower($mode);
        }

        if($mode == 'task') {
            $isHttp = false;
        } else if($mode == 'http') {
            $isCli = false;
        }

        if(!isset($this->request['cacheId'])) {
            $this->request->query->purge = 'app';
        }

        if($isCli && extension_loaded('apcu') && ini_get('apc.enable_cli')) {
            $count = $this->_clearApcu();
            $this->io->writeLine('Cleared '.$count.' CLI APCU entries');
        }

        if($isHttp) {
            $config = $this->getConfig('core/application/http/Config');
            $mode = $this->application->getEnvironmentMode();

            $baseUrl = $config->getRootUrl($mode);
            $credentials = $config->getCredentials($mode);

            $url = new link\http\Url('http://'.rtrim($baseUrl, '/').'/cache/apcu-clear.json');
            $url->query->import($this->request->query);

            if($credentials !== null) {
                $url->setCredentials(
                    $credentials['username'],
                    $credentials['password']
                );
            }

            //$this->io->writeLine($url);

            $http = new link\http\Client();
            $response = $http->get($url); // TODO use localhost ip?

            if($response->isOk()) {
                $json = $response->getJsonContent();
                $cleared = @$json['cleared'];

                if($cleared === null) {
                    $this->io->writeLine('APCU unable to pass IP check via '.@$json['addr']);
                } else {
                    $this->io->writeLine('Cleared '.$cleared.' HTTP APCU entries via '.@$json['addr']);
                }
            } else {
                $this->io->writeErrorLine('Http call failed :(');
                //$this->io->writeErrorLine($response->getContent());
            }
        }
    }
}