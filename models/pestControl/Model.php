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

class Model extends axis\Model {
    

    public function logAccessError($code=403, $request=null, $message=null) {
        return $this->accessLog->logAccess($code, $request, $message);
    }

    public function logNotFound($request=null, $message=null) {
        return $this->missLog->logMiss($request, $message);
    }

    public function logException(\Exception $exception, $request=null) {
        return $this->errorLog->logException($exception, $request);
    }



    public function normalizeLogRequest($request) {
        if($request === null) {
            $request = '/';

            try {
                if(df\Launchpad::$application instanceof arch\IDirectoryRequestApplication) {
                    $request = df\Launchpad::$application->getContext()->request->toString();
                }
            } catch(\Exception $e) {}
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