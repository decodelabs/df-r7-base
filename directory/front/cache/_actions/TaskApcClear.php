<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\cache\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\link;

class TaskApcClear extends arch\task\Action {
    
    use TApcClear;

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

        if(!isset($this->request->query->cacheId)) {
            $this->request->query->purge = 'app';
        }

        if($isCli && extension_loaded('apc') && ini_get('apc.enable_cli')) {
            $count = $this->_clearApc();
            $this->io->writeLine('Cleared '.$count.' CLI APC entries');
        }

        if($isHttp) {
            $config = $this->getConfig('core/application/http/Config');
            $baseUrls = $config->values->baseUrl->toArray();
            $credentials = null;

            /*
            if(isset($baseUrls['production']) && substr($baseUrls['production'], 0, 11) != 'production.') {
                $baseUrl = $baseUrls['production'];
            } else 
            */
            if(isset($baseUrls['testing']) && substr($baseUrls['testing'], 0, 8) != 'testing.') {
                $baseUrl = $baseUrls['testing'];
                $credentials = $config->getCredentials('testing');
            } else if(isset($baseUrls['development'])) {
                $baseUrl = $baseUrls['development'];
                $credentials = $config->getCredentials('development');
            } else {
                $this->throwError(500, 'Cannot find a suitable base url in config');
            }

            $url = new link\http\Url('http://'.rtrim($baseUrl, '/').'/cache/apc-clear.json');
            $url->query->import($this->request->query);

            if($credentials !== null) {
                $url->setCredentials(
                    $credentials['username'],
                    $credentials['password']
                );
            }

            //$this->io->writeLine($url);

            $http = new link\http\Client();
            $response = $http->get($url)->sync(); // TODO use localhost ip?

            if($response->isOk()) {
                $json = $this->data->jsonDecode($response->getContent());
                $cleared = @$json['cleared'];

                if($cleared === null) {
                    $this->io->writeLine('APC unable to pass IP check via '.@$json['addr']);
                } else {
                    $this->io->writeLine('Cleared '.$cleared.' HTTP APC entries via '.@$json['addr']);
                }
            } else {
                $this->io->writeErrorLine('Http call failed :(');
            }
        }
    }
}