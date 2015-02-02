<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\pestControl;

use df;
use df\core;
use df\apex;
use df\axis;
use df\arch;
use df\link;

class Model extends axis\Model {
    
    const PURGE_THRESHOLD = '2 months';

    public function logAccessError($code=403, $request=null, $message=null) {
        return $this->accessLog->logAccess($code, $request, $message);
    }

    public function logNotFound($request=null, $message=null) {
        return $this->missLog->logMiss($request, $message);
    }

    public function logException(\Exception $exception, $request=null) {
        return $this->errorLog->logException($exception, $request);
    }


    public function getPurgeThreshold() {
        return self::PURGE_THRESHOLD;
    }



    public function normalizeLogRequest($request, $mode=null) {
        if($request === null) {
            $request = '/';

            try {
                if(df\Launchpad::$application instanceof core\application\Http) {
                    $request = df\Launchpad::$application->getContext()->http->getRequest()->getUrl();
                } else if(df\Launchpad::$application instanceof arch\IDirectoryRequestApplication) {
                    $request = df\Launchpad::$application->getContext()->request;
                }
            } catch(\Exception $e) {}
        }

        if($mode == 'Http') {
            if((is_string($request) && preg_match('/^[a-z]+\:\/\//i', $request))
            || $request instanceof arch\IRequest) {
                $request = $this->context->uri($request);
            }

            if($request instanceof link\http\IUrl) {
                $router = core\application\http\Router::getInstance();
                $request = new core\uri\Url($request->getLocalString());
                $router->mapPath($request->path);
                $request = ltrim((string)$request, '/');

                if(!strlen($request)) {
                    $request = '/';
                }
            }
        }

        return (string)$request;
    }

    public function getLogUserId() {
        $user = null;

        try {
            $user = $this->context->user->isLoggedIn() ? 
                $this->context->user->client->getId() : 
                null;
        } catch(\Exception $e) {}

        return $user;
    }

    public function getLogReferrer() {
        if(df\Launchpad::$application instanceof core\application\Http) {
            return df\Launchpad::$application->getContext()->http->getReferrer();
        }

        return null;
    }
}