<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\devtools\cache\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\link;

class TaskApcClear extends arch\task\Action {
    
    use apex\directory\devtools\cache\_actions\TApcClear;

    const DEFAULT_ACCESS = arch\IAccess::ALL;
    const CHECK_ACCESS = false;

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

        if($isCli && extension_loaded('apc') && ini_get('apc.enable_cli')) {
            $count = $this->_clearApc();
            $this->response->writeLine('Cleared '.$count.' CLI APC entries');
        }

        if($isHttp) {
            $this->response->writeLine('Calling HTTP APC cache clear action...');
            $config = core\application\Http_Config::getInstance($this->application);
            $baseUrls = @(array)$config->values['baseUrl'];

            /*
            if(isset($baseUrls['production']) && substr($baseUrls['production'], 0, 11) != 'production.') {
                $baseUrl = $baseUrls['production'];
            } else 
            */
            if(isset($baseUrls['testing']) && substr($baseUrls['testing'], 0, 8) != 'testing.') {
                $baseUrl = $baseUrls['testing'];
            } else if(isset($baseUrls['development'])) {
                $baseUrl = $baseUrls['development'];
            } else {
                $this->throwError('Cannot find a suitable base url in config');
            }

            $url = new link\http\Url('http://'.rtrim($baseUrl, '/').'/~devtools/cache/apc-clear.json');
            $url->query->import($this->request->query);
            $this->response->writeLine($url);

            $httpClient = new link\http\Client();
            $response = $httpClient->get($url);

            if($response->isOk()) {
                $json = json_decode($response->getContent(), true);
                $this->response->writeLine('Cleared '.@$json['cleared'].' HTTP APC entries via '.@$json['addr']);
            } else {
                $this->response->writeErrorLine('Http call failed :(');
            }
        }
    }
}