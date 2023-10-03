<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\pestControl;

use DecodeLabs\Disciple;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;
use df\arch;

use df\axis;
use df\core;
use df\link;

class Model extends axis\Model
{
    public const PURGE_THRESHOLD = '1 month';
    public const LOG_BOTS = false;

    public function logCurrentAgent(bool $logBots = false): array
    {
        return $this->context->data->user->agent->logCurrent($logBots);
    }

    public function logAccessError($code = 403, $request = null, string $message = null)
    {
        return $this->accessLog->logAccess($code, $request, $message);
    }

    public function logNotFound($request = null, string $message = null): void
    {
        // Prepare
        $agent = $this->logCurrentAgent();
        $isBot = $agent['isBot'];

        if ($isBot && !static::LOG_BOTS) {
            return;
        }

        $mode = Genesis::$kernel->getMode();
        $request = $this->normalizeLogRequest($request, $mode, $url);

        if (!$this->miss->checkRequest($request)) {
            return;
        }


        // Find miss
        $missId = (string)$this->miss->select('id')
            ->where('request', '=', $request)
            ->toValue('id');

        $count = 0;

        if ($missId) {
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
                ->where('id', '=', $missId)
                ->execute();
        }


        // Insert or fetch id
        if (!$count) {
            $missId = (string)$this->miss->insert([
                'mode' => $mode,
                'request' => $request,
                'seen' => 1,
                'botsSeen' => $isBot ? 1 : 0,
                'firstSeen' => 'now',
                'lastSeen' => 'now'
            ])->execute()['id'];
        }


        if ($message !== null) {
            $message = utf8_encode($message);
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
                    'isProduction' => Genesis::$environment->isProduction()
                ])
                ->execute();
        }
    }

    public function logException(\Throwable $exception, $request = null)
    {
        return $this->errorLog->logException($exception, $request);
    }

    public function getPurgeThreshold()
    {
        return self::PURGE_THRESHOLD;
    }



    public function normalizeLogRequest($request, string $mode = null, string &$url = null): string
    {
        if ($request === null) {
            $request = '/';

            try {
                if (Genesis::$kernel->getMode() === 'Http') {
                    $request = Legacy::$http->getRequest()->getUrl();
                } else {
                    $request = Legacy::getContext()->request;
                }
            } catch (\Throwable $e) {
            }
        }

        if ($mode == 'Http') {
            if ((is_string($request) && preg_match('/^[a-z]+\:\/\//i', (string)$request))
            || $request instanceof arch\IRequest) {
                $request = $this->context->uri($request);
            }

            if ($request instanceof link\http\IUrl) {
                $url = (string)$request;
                $router = Legacy::$http->getRouter();
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
            $user = Disciple::isLoggedIn() ?
                Disciple::getId() :
                null;
        } catch (\Throwable $e) {
        }

        return $user;
    }

    public function getLogReferrer()
    {
        if (Genesis::$kernel->getMode() === 'Http') {
            return Legacy::$http->getReferrer();
        }

        return null;
    }
}
