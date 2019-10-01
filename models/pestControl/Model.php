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
    const PURGE_THRESHOLD = '1 month';

    public function logCurrentAgent(bool $logBots=false): array
    {
        return $this->context->data->user->agent->logCurrent($logBots);
    }

    public function logAccessError($code=403, $request=null, string $message=null)
    {
        return $this->accessLog->logAccess($code, $request, $message);
    }

    public function logNotFound($request=null, string $message=null): void
    {
        // Prepare
        $agent = $this->logCurrentAgent();
        $isBot = $agent['isBot'];
        $mode = $this->context->getRunMode();
        $request = $this->normalizeLogRequest($request, $mode, $url);

        if (!$this->miss->checkRequest($request)) {
            return;
        }


        // Update counts
        $count = $this->miss->update([
                'lastSeen' => 'now',
                'archiveDate' => null
            ])
            ->express('seen', 'seen', '+', 1)
            ->chainIf($isBot, function ($query) {
                $query->express('botsSeen', 'botsSeen', '+', 1);
            })
            ->where('request', '=', $request)
            ->execute();


        // Insert or fetch id
        if (!$count) {
            $missId = (string)$this->miss->insert([
                'mode' => $mode,
                'request' => $request,
                'seen' => 1,
                'botsSeen' => $isBot ? 1:0,
                'firstSeen' => 'now',
                'lastSeen' => 'now'
            ])->execute()['id'];
        } elseif (!$isBot) {
            $missId = (string)$this->miss->select('id')
                ->where('request', '=', $request)
                ->toValue('id');
        } else {
            $missId = null;
        }



        // Insert log
        if (!$isBot) {
            $this->missLog->insert([
                    'miss' => $missId,
                    'url' => $url,
                    'referrer' => $this->getLogReferrer(),
                    'message' => $message,
                    'userAgent' => $agent['id'],
                    'user' => $this->getLogUserId(),
                    'isProduction' => $this->context->app->isProduction()
                ])
                ->execute();
        }
    }

    public function logException(\Throwable $exception, $request=null)
    {
        return $this->errorLog->logException($exception, $request);
    }

    public function getPurgeThreshold()
    {
        return self::PURGE_THRESHOLD;
    }



    public function normalizeLogRequest($request, string $mode=null, string &$url=null): string
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
                $url = (string)$request;
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
