<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\log;

use df;
use df\core;
use df\apex;
use df\axis;
use df\arch;
    
class Model extends axis\Model {

    public function accessError($code=403, $request=null, $message=null) {
        $this->accessError->newRecord([
                'mode' => $this->context->getRunMode(),
                'code' => $code,
                'request' => $this->_normalizeRequest($request),
                'userAgent' => $this->_getUserAgent(),
                'message' => $message,
                'user' => $this->_getUser(),
                'isProduction' => $this->context->application->isProduction()
            ])
            ->save();

        return $this;
    }

    public function notFound($request=null, $message=null) {
        $this->notFound->newRecord([
                'mode' => $this->context->getRunMode(),
                'request' => $this->_normalizeRequest($request),
                'referrer' => $this->_getReferrer(),
                'message' => $message,
                'user' => $this->_getUser(),
                'isProduction' => $this->context->application->isProduction()
            ])
            ->save();

        return $this;
    }

    public function exception(\Exception $exception, $request=null) {
        $stackTrace = core\debug\StackTrace::fromException($exception);

        $this->criticalError->newRecord([
                'mode' => $this->context->getRunMode(),
                'request' => $this->_normalizeRequest($request),
                'userAgent' => $this->_getUserAgent(),
                'exceptionType' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => core\io\Util::stripLocationFromFilePath($exception->getFile()),
                'line' => $exception->getLine(),
                'stackTrace' => $stackTrace->toJson(),
                'user' => $this->_getUser(),
                'isProduction' => $this->context->application->isProduction()
            ])
            ->save();

        return $this;
    }

    protected function _normalizeRequest($request) {
        if($request === null) {
            $request = '/';

            try {
                if(df\Launchpad::$application instanceof arch\IDirectoryRequestApplication) {
                    $request = df\Launchpad::$application->getContext()->request->toString();
                }
            } catch(\Exception $e) {}
        }

        return $request;
    }

    protected function _getReferrer() {
        if(df\Launchpad::$application instanceof core\application\Http) {
            return df\Launchpad::$application->getContext()->http->getReferrer();
        }

        return null;
    }

    protected function _getUser() {
        $user = null;

        try {
            $user = $this->context->user->isLoggedIn() ? 
                $this->context->user->client->getId() : 
                null;
        } catch(\Exception $e) {}

        return $user;
    }

    protected function _getUserAgent() {
        $userAgent = null;

        try {
            $application = df\Launchpad::$application;

            if($application instanceof core\application\Http) {
                $userAgent = $application->getContext()->http->getUserAgent();
            } else if($application instanceof core\application\Task) {
                if(isset($_SERVER['TERM'])) {
                    $userAgent = $_SERVER['TERM'];
                } else if(isset($_SERVER['TERM_PROGRAM'])) {
                    $userAgent = $_SERVER['TERM_PROGRAM'];
                } else if(isset($_SERVER['TERMINAL'])) {
                    $userAgent = $_SERVER['TERMINAL'];
                } else {
                    $userAgent = 'Terminal';
                }
            }
        } catch(\Exception $e) {}

        return $userAgent;
    }
}