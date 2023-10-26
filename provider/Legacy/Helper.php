<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Legacy;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use DateInterval;
use DateTime;

use DecodeLabs\Deliverance;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Config\Theme as ThemeConfig;
use DecodeLabs\R7\Legacy\Plugins\Http as HttpPlugin;
use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Theme\Config as ThemeConfigInterface;
use DecodeLabs\Systemic;
use DecodeLabs\Systemic\Command;
use DecodeLabs\Systemic\Process;
use DecodeLabs\Systemic\Result as ProcessResult;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\Veneer\LazyLoad;
use DecodeLabs\Veneer\Plugin;

use df\arch\Context;
use df\arch\mail\Base as ArchMail;
use df\arch\Request;
use df\aura\view\content\Template;
use df\aura\view\IHtmlView as View;
use df\axis\IUnit as Unit;
use df\axis\Model;
use df\axis\unit\Cache as CacheUnit;
use df\axis\unit\Enum as EnumUnit;
use df\axis\unit\Table as TableUnit;
use df\core\app\Base as AppBase;
use df\core\collection\Tree;
use df\core\IRegistryObject as RegistryObject;
use df\core\loader\Base as LoaderBase;
use df\core\time\Date;
use df\core\time\Duration;
use df\core\uri\IUrl as Url;
use df\fire\ISlotContent as Slot;
use df\flex\Guid;
use df\flow\Manager as CommsManager;
use df\mesh\entity\Locator as EntityLocator;
use df\mesh\job\IJob as Job;
use df\mesh\job\IQueue as JobQueue;
use df\opal\query\ISelectQuery as SelectQuery;
use df\opal\record\IPartial as Partial;
use df\opal\record\IRecord as Record;
use df\user\IClientDataObject as ClientObject;
use df\user\Manager as UserManager;

use Stringable;

class Helper
{
    #[Plugin]
    #[LazyLoad]
    public HttpPlugin $http;

    protected ?Context $context = null;


    /**
     * Get app from container
     */
    public function app(): AppBase
    {
        static $app;

        if (!isset($app)) {
            $app = Genesis::$container['app'];
        }

        return $app;
    }

    /**
     * Get loader from container
     */
    public function getLoader(): LoaderBase
    {
        static $loader;

        if (!isset($loader)) {
            $loader = Genesis::$container['app.loader'];
        }

        return $loader;
    }






    /**
     * Get pass key
     */
    public function getPassKey(): string
    {
        return $this->app()::PASS_KEY;
    }

    /**
     * Get unique prefix
     */
    public function getUniquePrefix(): string
    {
        return $this->app()::UNIQUE_PREFIX;
    }



    /**
     * Set active context
     */
    public function setActiveContext(Context $context): void
    {
        $this->context = $context;
    }

    /**
     * Get active context
     */
    public function getActiveContext(): ?Context
    {
        return $this->context;
    }

    /**
     * Get current arch context
     */
    public function getContext(): Context
    {
        return $this->context ?? new Context(new Request('/'));
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
     * Get theme ID for area
     */
    public function getThemeIdFor(string $area): string
    {
        return $this->getThemeConfig()->getThemeIdFor($area);
    }

    /**
     * Get theme map
     *
     * @return array<string, string>
     */
    public function getThemeMap(): array
    {
        return $this->getThemeConfig()->getThemeMap();
    }


    /**
     * Get theme config
     */
    protected function getThemeConfig(): ThemeConfigInterface
    {
        static $output;

        if (!isset($output)) {
            if (Genesis::$container->has(ThemeConfigInterface::class)) {
                $output = Genesis::$container[ThemeConfigInterface::class];
            } else {
                $output = ThemeConfig::load();
            }
        }

        return $output;
    }




    /**
     * Dispatch child node
     *
     * @return mixed
     */
    public function dispatchChildNode(
        View $view,
        string $name
    ): mixed {
        $context = $this->getContext();

        $request = clone $context->request;
        $request->setNode($name);

        $action = $context->apex->getNode($request);
        $action->view = $view;

        return $action->dispatch();
    }


    /**
     * Expand a URI
     */
    public function uri(
        string|Stringable|Url $uri,
        string|Stringable|Url|bool|null $from = null,
        string|Stringable|Url|bool|null $to = null,
        bool $asRequest = false
    ): Url {
        return $this->getContext()->uri->__invoke($uri, $from, $to, $asRequest);
    }



    /**
     * Show a flash message
     */
    public function flashMessage(
        string $id,
        string $message,
        string $type = 'info'
    ): void {
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
    public function loadTemplate(
        string $path,
        array|callable|null $slots = null
    ): Template {
        return $this->getContext()->apex->template($path, $slots);
    }



    /**
     * Emit mesh event
     *
     * @param array<string, mixed>|null $data
     */
    public function emitEvent(
        string|EntityLocator $entity,
        string $action,
        array $data = null,
        JobQueue $jobQueue = null,
        Job $job = null
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
     */
    public function normalizeDate(mixed $date): Date
    {
        /**
         * @var Date
         */
        return Date::factory($date);
    }

    /**
     * Normalize duration
     */
    public function normalizeDuration(mixed $duration): Duration
    {
        /**
         * @var Duration
         */
        return Duration::factory($duration);
    }

    /**
     * Prepare internal date
     */
    public function prepareDate(mixed $date): ?Carbon
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
     */
    public function prepareInterval(mixed $interval): ?CarbonInterval
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
                $interval = ceil($interval->getDays()) . ' days';
            } else {
                $interval = $interval->getMonths() . ' months';
            }

            $interval = CarbonInterval::createFromDateString($interval);
            $interval->cascade();
        }

        return CarbonInterval::instance($interval);
    }

    /**
     * Format legacy duration
     */
    public function formatDuration(
        string|Stringable|int|Duration|DateInterval|null $duration,
        int $maxUnits = 1,
        bool $shortUnits = false,
        int|string $maxUnit = Duration::YEARS,
        bool $roundLastUnit = true
    ): ?string {
        return $this->getContext()->date->formatDuration(
            $duration,
            $maxUnits,
            $shortUnits,
            $maxUnit,
            $roundLastUnit
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
     */
    public function guid(
        string|Guid|null $guid
    ): Guid {
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
     */
    public function shortenGuid(
        string|Guid $guid
    ): string {
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
     * Get relation ID
     *
     * @param array<string, mixed>|Record|Partial|null $record
     */
    public function getRelationId(
        array|Record|Partial|null $record,
        string $field,
        ?string $idField = null
    ): ?string {
        $output = $this->getContext()->data->getRelationId($record, $field, $idField);

        if ($output !== null) {
            $output = (string)$output;
        }

        return $output;
    }



    /**
     * Select row from unit or die
     *
     * @param string|array<string> $fields
     * @return array<string, mixed>
     */
    public function selectForAction(
        string|Unit $source,
        string|array $fields,
        mixed $primary = null,
        ?callable $queryChain = null
    ): array {
        return $this->getContext()->data->selectForAction(
            $source,
            $fields,
            $primary,
            $queryChain
        );
    }


    /**
     * Apply query for action
     *
     * @param SelectQuery<array<string, mixed>> $query
     * @return array<string, mixed>
     */
    public function queryForAction(
        SelectQuery $query,
        ?callable $chain = null
    ): array {
        return $this->getContext()->data->queryForAction($query, $chain);
    }

    /**
     * Apply query for action with primary clause
     *
     * @param SelectQuery<array<string, mixed>> $query
     * @return array<string, mixed>
     */
    public function queryByPrimaryForAction(
        SelectQuery $query,
        mixed $primary,
        ?callable $chain = null
    ): array {
        return $this->getContext()->data->queryByPrimaryForAction(
            $query,
            $primary,
            $chain
        );
    }




    /**
     * Normalize block
     */
    public function normalizeBlock(mixed $block): ?Block
    {
        return $this->getContext()->nightfire->normalizeBlock($block);
    }

    /**
     * Normalize slot
     */
    public function normalizeSlot(mixed $slot): ?Slot
    {
        return $this->getContext()->nightfire->normalizeSlot($slot);
    }



    /**
     * Send prepared email
     *
     * @param array<string, mixed>|null $slots
     */
    public function sendPreparedMail(string $path, ?array $slots = null, bool $forceSend = false): void
    {
        $this->getContext()->comms->sendPreparedMail($path, $slots, $forceSend);
    }

    /**
     * Create prepared email
     *
     * @param array<string, mixed>|null $slots
     */
    public function prepareMail(string $path, ?array $slots = null, bool $forceSend = false): ArchMail
    {
        return $this->getContext()->comms->prepareMail($path, $slots, $forceSend);
    }


    /**
     * Render prepared email
     *
     * @param array<string, mixed>|null $slots
     */
    public function renderMail(string $path, ?array $slots = null, bool $forceSend = false): string
    {
        return $this->prepareMail($path, $slots, $forceSend)->getBodyHtml();
    }


    /**
     * New task command
     */
    public function taskCommand(
        string|Request $request,
        bool $dfSource = false
    ): Command {
        $request = Request::factory($request);
        $args = [$request];

        if ($dfSource) {
            $args[] = '--df-source';
        }

        return Systemic::scriptCommand([$this->getEntryFile(), ...$args])
            ->setWorkingDirectory(Genesis::$hub->getApplicationPath());
    }

    /**
     * Get entry file
     */
    public function getEntryFile(): string
    {
        $path = Genesis::$hub->getApplicationPath() . '/entry/';
        $path .= Genesis::$environment->getName() . '.php';
        return $path;
    }

    /**
     * Run r7 task in foreground
     */
    public function runTask(
        string|Request $request,
        bool $dfSource = false
    ): bool {
        return $this->taskCommand($request, $dfSource)
            ->run();
    }

    /**
     * Run task quietly
     */
    public function runTaskQuietly(
        string|Request $request,
        bool $dfSource = false
    ): void {
        if (Genesis::$kernel->getMode() === 'Task') {
            $session = Cli::getSession();
            $oldBroker = $session->getBroker();
            $session->setBroker(Deliverance::newBroker());

            $this->getContext()->task->invoke($request);
            $session->setBroker($oldBroker);
        } else {
            $this->launchTask($request, $dfSource);
        }
    }

    /**
     * Run r7 task in foreground
     */
    public function captureTask(
        string|Request $request,
        bool $dfSource = false
    ): ProcessResult {
        return $this->taskCommand($request, $dfSource)
            ->capture();
    }


    /**
     * Launch a foreground task
     */
    public function launchTask(
        string|Request $request,
        bool $dfSource = false
    ): Process {
        return $this->taskCommand($request, $dfSource)
            ->launch();
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










    /**
     * Set registry object
     */
    public function setRegistryObject(RegistryObject $object): void
    {
        $this->app()->setRegistryObject($object);
    }

    /**
     * Get registry object
     */
    public function getRegistryObject(string $key): ?RegistryObject
    {
        return $this->app()->getRegistryObject($key);
    }

    /**
     * Has registry object
     */
    public function hasRegistryObject(string $key): bool
    {
        return $this->app()->hasRegistryObject($key);
    }

    /**
     * Remove registry object
     */
    public function removeRegistryObject(string $key): void
    {
        $this->app()->removeRegistryObject($key);
    }

    /**
     * @return array<string, RegistryObject>
     */
    public function findRegistryObjects(string $beginningWith): array
    {
        return $this->app()->findRegistryObjects($beginningWith);
    }

    /**
     * @return array<string, RegistryObject>
     */
    public function getRegistryObjects(): array
    {
        return $this->app()->getRegistryObjects();
    }
}
