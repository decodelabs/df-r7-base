<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Legacy;

use df\arch\Context;
use df\arch\Request;
use df\arch\mail\Base as ArchMail;

use df\aura\view\content\Template;
use df\aura\view\IHtmlView as View;

use df\axis\Model;
use df\axis\IUnit as Unit;
use df\axis\unit\Cache as CacheUnit;
use df\axis\unit\Table as TableUnit;
use df\axis\unit\Enum as EnumUnit;
use df\axis\unit\Config as ConfigUnit;

use df\core\collection\Tree;
use df\flex\Guid;

use df\link\http\response\Redirect as RedirectResponse;
use df\link\http\ICookie as Cookie;

use df\flow\Manager as CommsManager;

use df\mesh\job\IQueue as JobQueue;
use df\mesh\job\IJob as Job;
use df\mesh\entity\Locator as EntityLocator;

use df\core\uri\IUrl as Url;

use df\core\time\Date;
use df\core\time\Duration;

use df\fire\IBlock as Block;
use df\fire\ISlotContent as Slot;

use df\opal\query\ISelectQuery as SelectQuery;
use df\opal\record\IRecord as Record;
use df\opal\record\IPartial as Partial;

use df\user\Manager as UserManager;
use df\user\IClientDataObject as ClientObject;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateTime;
use DateInterval;

use DecodeLabs\Exceptional;
use DecodeLabs\Systemic\Process;
use DecodeLabs\Systemic\Process\Result as ProcessResult;
use DecodeLabs\Tagged\Markup;
use DecodeLabs\Terminus\Session as TerminusSession;

use Stringable;
use Throwable;

class Helper
{
    /**
     * Get current arch context
     */
    protected function getContext(): Context
    {
        /**
         * @var Context
         */
        return Context::getCurrent();
    }


    /**
     * Get request query value
     */
    public function getQueryVar(string $key): ?string
    {
        $output = $this->getContext()->request[$key];

        if ($output === '') {
            $output = null;
        }

        return $output;
    }

    /**
     * Has request query value
     */
    public function hasQueryVar(string $key): bool
    {
        return isset($this->getContext()->request[$key]);
    }

    /**
     * Check query var exists
     */
    public function queryVarExists(string $key): bool
    {
        return isset($this->getContext()->request->query->{$key});
    }

    /**
     * Get request query
     *
     * @return Tree<string, mixed>
     */
    public function getQuery(): Tree
    {
        return $this->getContext()->request->query;
    }

    /**
     * Get request
     */
    public function getRequest(): Request
    {
        return $this->getContext()->request;
    }

    /**
     * Does the current request match this pattern?
     */
    public function requestMatches(string $pattern): bool
    {
        return $this->getContext()->request->matches($pattern);
    }

    /**
     * Get request redirect from
     */
    public function getRequestRedirectFrom(): ?Request
    {
        return $this->getContext()->request->getRedirectFrom();
    }

    /**
     * Get request redirect to
     */
    public function getRequestRedirectTo(): ?Request
    {
        return $this->getContext()->request->getRedirectTo();
    }


    /**
     * Create redirect with active request
     */
    public function redirect(callable $builder): RedirectResponse
    {
        $context = $this->getContext();
        return $context->http->redirect($builder(clone $context->request));
    }

    /**
     * Apply redirect with active request
     */
    public function redirectNow(callable $builder): Throwable
    {
        return $this->getContext()->forceResponse(
            $this->redirect($builder)
        );
    }


    /**
     * Is AJAX request?
     */
    public function isAjaxRequest(): bool
    {
        return $this->getContext()->http->isAjaxRequest();
    }


    /**
     * Dispatch child node
     *
     * @return mixed
     */
    public function dispatchChildNode(View $view, string $name)
    {
        $context = $this->getContext();

        $request = clone $context->request;
        $request->setNode($name);

        $action = $context->apex->getNode($request);
        $action->view = $view;

        return $action->dispatch();
    }


    /**
     * Expand a URI
     *
     * @param string|Stringable|Url $uri
     * @param string|Stringable|Url|bool|null $from
     * @param string|Stringable|Url|bool|null $to
     */
    public function uri($uri, $from=null, $to=null, bool $asRequest=false): Url
    {
        return $this->getContext()->uri->__invoke($uri, $from, $to, $asRequest);
    }



    /**
     * Set cookie
     */
    public function setCookie(string $key, ?string $value): Cookie
    {
        return $this->getContext()->http->setCookie($key, $value);
    }

    /**
     * Get cookie
     */
    public function getCookie(string $key): ?string
    {
        try {
            $http = $this->getContext()->http;
        } catch (Throwable $e) {
            return null;
        }

        return $http->getCookie($key);
    }

    /**
     * Remove cookie
     */
    public function removeCookie(string $key): void
    {
        try {
            $http = $this->getContext()->http;
        } catch (Throwable $e) {
            return;
        }

        $http->removeCookie($key);
    }


    /**
     * Get cookies
     */
    public function getCookies(): Tree
    {
        try {
            $http = $this->getContext()->http;
        } catch (Throwable $e) {
            return new Tree();
        }

        return $http->getCookies();
    }




    /**
     * Show a flash message
     */
    public function flashMessage(string $id, string $message, string $type='info'): void
    {
        $this->getContext()->comms->flash($id, $message, $type);
    }

    /**
     * Get comms manager
     */
    public function getCommsManager(): CommsManager
    {
        return $this->getContext()->comms->getManager();
    }




    /**
     * Load template
     *
     * @param array<string, mixed>|callable|null $slots
     */
    public function loadTemplate(string $path, $slots=null): Template
    {
        return $this->getContext()->apex->template($path, $slots);
    }



    /**
     * Emit mesh event
     *
     * @param string|EntityLocator $entity
     * @param array<string, mixed>|null $data
     */
    public function emitEvent(
        $entity,
        string $action,
        array $data=null,
        JobQueue $jobQueue=null,
        Job $job=null
    ): void {
        $this->getContext()->mesh->emitEvent($entity, $action, $data, $jobQueue, $job);
    }





    /**
     * Date object to string
     */
    public function dateToString(?Date $date): ?string
    {
        if (!$date) {
            return null;
        }

        return (string)$date;
    }

    /**
     * Normalize date
     *
     * @param string|Stringable|int|Date|DateTime|null $date
     */
    public function normalizeDate($date): Date
    {
        /**
         * @var Date
         */
        return Date::factory($date);
    }

    /**
     * Normalize duration
     *
     * @param string|Stringable|int|Duration|DateInterval|null $duration
     */
    public function normalizeDuration($duration): Duration
    {
        /**
         * @var Duration
         */
        return Duration::factory($duration);
    }

    /**
     * Prepare internal date
     *
     * @param string|Stringable|int|Date|DateTime|null $date
     */
    public function prepareDate($date): ?Carbon
    {
        if ($date === null) {
            return null;
        }

        if (!is_object($date)) {
            $date = Date::factory($date);
        }

        if ($date instanceof Date) {
            $date = $date->getRaw();
        }

        if (!$date instanceof DateTime) {
            return null;
        }

        return Carbon::instance($date);
    }


    /**
     * Prepare internal interval
     *
     * @param string|Stringable|int|Duration|DateInterval|null $interval
     */
    public function prepareInterval($interval): ?CarbonInterval
    {
        if ($interval === null) {
            return null;
        }

        if (!is_object($interval)) {
            $interval = Duration::factory($interval);
        }

        if ($interval instanceof Duration) {
            $months = $interval->getMonths();

            if ((int)$months != $months) {
                $interval = ceil($interval->getDays()).' days';
            } else {
                $interval = $interval->getMonths().' months';
            }

            $interval = CarbonInterval::createFromDateString($interval);
            $interval->cascade();
        }

        return CarbonInterval::instance($interval);
    }

    /**
     * Format legacy duration
     *
     * @param string|Stringable|int|Duration|DateInterval|null $duration
     * @param int|string $maxUnit
     */
    public function formatDuration(
        $duration,
        int $maxUnits=1,
        bool $shortUnits=false,
        $maxUnit=Duration::YEARS,
        bool $roundLastUnit=true
    ): ?string {
        return $this->getContext()->date->formatDuration(
            $duration, $maxUnits, $shortUnits, $maxUnit, $roundLastUnit
        );
    }




    /**
     * Get user manager
     */
    public function getUserManager(): UserManager
    {
        return $this->getContext()->user;
    }

    /**
     * Get user client data object
     */
    public function getClient(): ClientObject
    {
        return $this->getContext()->user->client;
    }





    /**
     * Ensure GUID
     *
     * @param string|Guid|null $guid
     */
    public function guid($guid): Guid
    {
        return Guid::factory($guid);
    }


    /**
     * New GUID
     */
    public function newGuid(): Guid
    {
        return Guid::comb();
    }


    /**
     * Shorten Guid
     *
     * @param string|Guid $guid
     */
    public function shortenGuid($guid): string
    {
        return Guid::shorten((string)$guid);
    }

    /**
     * Unshorten Guid
     */
    public function unshortenGuid(string $id): string
    {
        return Guid::unshorten($id);
    }



    /**
     * Load table unit from ID
     */
    public function getTable(string $id): TableUnit
    {
        /**
         * @var TableUnit
         */
        return Model::loadUnitFromId($id);
    }

    /**
     * Load enum unit from ID
     */
    public function getEnum(string $id): EnumUnit
    {
        /**
         * @var EnumUnit
         */
        return Model::loadUnitFromId($id);
    }

    /**
     * Load cache unit from ID
     */
    public function getCache(string $id): CacheUnit
    {
        /**
         * @var CacheUnit
         */
        return Model::loadUnitFromId($id);
    }

    /**
     * Load config unit from ID
     */
    public function getConfig(string $id): ConfigUnit
    {
        /**
         * @var ConfigUnit
         */
        return Model::loadUnitFromId($id);
    }


    /**
     * Get relation ID
     *
     * @param array<string, mixed>|Record|Partial|null $record
     */
    public function getRelationId($record, string $field, ?string $idField=null): ?string
    {
        $output = $this->getContext()->data->getRelationId($record, $field, $idField);

        if ($output !== null) {
            $output = (string)$output;
        }

        return $output;
    }



    /**
     * Select row from unit or die
     *
     * @param string|Unit $source
     * @param string|array<string> $fields
     * @param mixed $primary
     * @return array<string, mixed>
     */
    public function selectForAction($source, $fields, $primary=null, ?callable $queryChain=null): array
    {
        return $this->getContext()->data->selectForAction(
            $source, $fields, $primary, $queryChain
        );
    }


    /**
     * Apply query for action
     *
     * @param SelectQuery<array<string, mixed>> $query
     * @return array<string, mixed>
     */
    public function queryForAction(SelectQuery $query, ?callable $chain=null): array
    {
        return $this->getContext()->data->queryForAction($query, $chain);
    }

    /**
     * Apply query for action with primary clause
     *
     * @param SelectQuery<array<string, mixed>> $query
     * @param mixed $primary
     * @return array<string, mixed>
     */
    public function queryByPrimaryForAction(SelectQuery $query, $primary, ?callable $chain=null): array
    {
        return $this->getContext()->data->queryByPrimaryForAction(
            $query, $primary, $chain
        );
    }




    /**
     * Normalize block
     *
     * @param mixed $block
     */
    public function normalizeBlock($block): ?Block
    {
        return $this->getContext()->nightfire->normalizeBlock($block);
    }

    /**
     * Normalize slot
     *
     * @param mixed $slot
     */
    public function normalizeSlot($slot): ?Slot
    {
        return $this->getContext()->nightfire->normalizeSlot($slot);
    }



    /**
     * Send prepared email
     *
     * @param array<string, mixed>|null $slots
     */
    public function sendPreparedMail(string $path, ?array $slots=null, bool $forceSend=false): void
    {
        $this->getContext()->comms->sendPreparedMail($path, $slots, $forceSend);
    }

    /**
     * Create prepared email
     *
     * @param array<string, mixed>|null $slots
     */
    public function prepareMail(string $path, ?array $slots=null, bool $forceSend=false): ArchMail
    {
        return $this->getContext()->comms->prepareMail($path, $slots, $forceSend);
    }


    /**
     * Render prepared email
     *
     * @param array<string, mixed>|null $slots
     */
    public function renderMail(string $path, ?array $slots=null, bool $forceSend=false): string
    {
        return $this->prepareMail($path, $slots, $forceSend)->getBodyHtml();
    }



    /**
     * Launch a foreground task
     */
    public function launchTask(
        string $request,
        ?TerminusSession $session=null,
        ?string $user=null,
        bool $dfSource=false,
        bool $decoratable=null
    ): ProcessResult {
        return $this->getContext()->task->launch(
            $request, $session, $user, $dfSource, $decoratable
        );
    }

    /**
     * Launch a background task
     */
    public function launchBackgroundTask(
        string $request,
        ?string $user=null,
        bool $dfSource=false,
        bool $decoratable=null
    ): Process {
        return $this->getContext()->task->launchBackground(
            $request, $user, $dfSource, $decoratable
        );
    }



    /**
     * Get list of currency names
     *
     * @return array<string, string>
     */
    public function getCurrencyNames(): array
    {
        return $this->getContext()->i18n->numbers->getCurrencyList();
    }
}
