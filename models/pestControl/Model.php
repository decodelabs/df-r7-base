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

class Model extends axis\Model
{
    const PURGE_THRESHOLD = '2 months';

    public function logAccessError($code=403, $request=null, $message=null)
    {
        return $this->accessLog->logAccess($code, $request, $message);
    }

    public function logNotFound($request=null, $message=null)
    {
        return $this->missLog->logMiss($request, $message);
    }

    public function logException(\Throwable $exception, $request=null)
    {
        return $this->errorLog->logException($exception, $request);
    }

    public function logDeprecated($message, $request=null)
    {
        return $this->errorLog->logDeprecated($message, $request);
    }


    public function getPurgeThreshold()
    {
        return self::PURGE_THRESHOLD;
    }



    public function normalizeLogRequest($request, $mode=null)
    {
        if ($request === null) {
            $request = '/';

            try {
                if (df\Launchpad::$runner instanceof core\app\runner\Http) {
                    $request = df\Launchpad::$runner->getContext()->http->getRequest()->getUrl();
                } elseif (df\Launchpad::$runner instanceof core\IContextAware) {
                    $request = df\Launchpad::$runner->getContext()->request;
                }
            } catch (\Throwable $e) {
            }
        }

        if ($mode == 'Http') {
            if ((is_string($request) && preg_match('/^[a-z]+\:\/\//i', $request))
            || $request instanceof arch\IRequest) {
                $request = $this->context->uri($request);
            }

            if ($request instanceof link\http\IUrl) {
                $router = core\app\runner\http\Router::getInstance();
                $request = new core\uri\Url($request->getLocalString());
                unset($request->query->cts);
                $router->getRootMap()->mapPath($request->path);
                $request = ltrim((string)$request, '/');

                if (!strlen($request)) {
                    $request = '/';
                }
            }
        }

        return (string)$request;
    }

    public function getLogUserId()
    {
        $user = null;

        try {
            $user = $this->context->user->isLoggedIn() ?
                $this->context->user->client->getId() :
                null;
        } catch (\Throwable $e) {
        }

        return $user;
    }

    public function getLogReferrer()
    {
        if (df\Launchpad::$runner instanceof core\app\runner\Http) {
            return df\Launchpad::$runner->getContext()->http->getReferrer();
        }

        return null;
    }
}
