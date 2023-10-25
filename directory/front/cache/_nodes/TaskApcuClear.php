<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\cache\_nodes;

use DecodeLabs\Genesis;
use DecodeLabs\R7\Config\Http as HttpConfig;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus as Cli;
use df\arch;
use df\flex;

use GuzzleHttp\Client as HttpClient;

class TaskApcuClear extends arch\node\Task
{
    use TApcuClear;

    public const DEFAULT_ACCESS = arch\IAccess::ALL;
    public const OPTIMIZE = true;

    public function execute(): void
    {
        $mode = $this->request->query->get('mode');
        unset($this->request->query->mode);
        $isHttp = $isCli = true;

        if ($mode) {
            $mode = strtolower((string)$mode);
        }

        if ($mode == 'task') {
            $isHttp = false;
        } elseif ($mode == 'http') {
            $isCli = false;
        }

        if (!isset($this->request['cacheId'])) {
            $this->request->query->purge = 'app';
        }

        if ($isCli && extension_loaded('apcu') && ini_get('apc.enable_cli')) {
            $count = $this->_clearApcu();
            Cli::info('Cleared ' . $count . ' CLI APCU entries');
        }

        if ($isHttp) {
            $router = Legacy::$http->getRouter();
            $url = clone $router->getRootUrl();
            $url->path->push('/cache/apcu-clear.json');
            $url->query->import($this->request->query);

            $config = HttpConfig::load();
            $credentials = $config->getCredentials(Genesis::$environment->getMode());

            //Cli::info($url);

            $httpClient = new HttpClient();

            try {
                $auth = null;

                if ($credentials !== null) {
                    $auth = [
                        $credentials['username'] ?? null,
                        $credentials['password'] ?? null
                    ];
                }

                $response = $httpClient->get((string)$url, [
                    'verify' => false,
                    'auth' => $auth,
                    'headers' => [
                        'x-df-self' => Legacy::$http->getDfSelfKey()
                    ]
                ]);
            } catch (\Exception $e) {
                Cli::error('Http call failed :(');
                return;
            }

            $json = flex\Json::stringToTree((string)$response->getBody());

            $cleared = $json['cleared'];
            $type = $url->isSecure() ? 'HTTPS' : 'HTTP';

            if ($cleared === null) {
                Cli::warning('APCU unable to pass IP check via ' . $json['addr']);
            } else {
                Cli::notice('Cleared ' . $cleared . ' ' . $type . ' APCU entries via ' . $json['addr']);
            }
        }
    }
}
