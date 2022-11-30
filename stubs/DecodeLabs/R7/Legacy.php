<?php
/**
 * This is a stub file for IDE compatibility only.
 * It should not be included in your projects.
 */

namespace DecodeLabs\R7;

use Carbon\Carbon as Ref15;
use Carbon\CarbonInterval as Ref16;
use DateInterval as Ref17;
use DecodeLabs\R7\Legacy\Helper as Inst;
use DecodeLabs\R7\Legacy\Plugins\Http as HttpPlugin;
use DecodeLabs\R7\Nightfire\Block as Ref29;
use DecodeLabs\Systemic\Process as Ref34;
use DecodeLabs\Systemic\Result as Ref33;
use DecodeLabs\Terminus\Session as Ref32;
use DecodeLabs\Veneer\Plugin\Wrapper as PluginWrapper;
use DecodeLabs\Veneer\Proxy;
use DecodeLabs\Veneer\ProxyTrait;
use df\arch\Context as Ref2;
use df\arch\mail\Base as Ref31;
use df\arch\Request as Ref4;
use df\aura\view\content\Template as Ref9;
use df\aura\view\IHtmlView as Ref5;
use df\axis\IUnit as Ref27;
use df\axis\unit\Cache as Ref23;
use df\axis\unit\Config as Ref24;
use df\axis\unit\Enum as Ref22;
use df\axis\unit\Table as Ref21;
use df\core\app\Base as Ref0;
use df\core\collection\Tree as Ref3;
use df\core\IRegistryObject as Ref35;
use df\core\loader\Base as Ref1;
use df\core\time\Date as Ref13;
use df\core\time\Duration as Ref14;
use df\core\uri\IUrl as Ref7;
use df\fire\ISlotContent as Ref30;
use df\flex\Guid as Ref20;
use df\flow\Manager as Ref8;
use df\mesh\entity\Locator as Ref10;
use df\mesh\job\IJob as Ref12;
use df\mesh\job\IQueue as Ref11;
use df\opal\query\ISelectQuery as Ref28;
use df\opal\record\IPartial as Ref26;
use df\opal\record\IRecord as Ref25;
use df\user\IClientDataObject as Ref19;
use df\user\Manager as Ref18;
use Stringable as Ref6;

class Legacy implements Proxy
{
    use ProxyTrait;

    public const VENEER = 'DecodeLabs\R7\Legacy';
    public const VENEER_TARGET = Inst::class;

    public static Inst $instance;
    /** @var HttpPlugin|PluginWrapper<HttpPlugin> $http */
    public static HttpPlugin|PluginWrapper $http;

    public static function app(): Ref0
    {
        return static::$instance->app();
    }
    public static function getLoader(): Ref1
    {
        return static::$instance->getLoader();
    }
    public static function getPassKey(): string
    {
        return static::$instance->getPassKey();
    }
    public static function getUniquePrefix(): string
    {
        return static::$instance->getUniquePrefix();
    }
    public static function setActiveContext(Ref2 $context): void
    {
    }
    public static function getActiveContext(): ?Ref2
    {
        return static::$instance->getActiveContext();
    }
    public static function getContext(): Ref2
    {
        return static::$instance->getContext();
    }
    public static function getQueryVar(string $key): ?string
    {
        return static::$instance->getQueryVar(...func_get_args());
    }
    public static function hasQueryVar(string $key): bool
    {
        return static::$instance->hasQueryVar(...func_get_args());
    }
    public static function queryVarExists(string $key): bool
    {
        return static::$instance->queryVarExists(...func_get_args());
    }
    public static function getQuery(): Ref3
    {
        return static::$instance->getQuery();
    }
    public static function getRequest(): Ref4
    {
        return static::$instance->getRequest();
    }
    public static function requestMatches(string $pattern): bool
    {
        return static::$instance->requestMatches(...func_get_args());
    }
    public static function getRequestRedirectFrom(): ?Ref4
    {
        return static::$instance->getRequestRedirectFrom();
    }
    public static function getRequestRedirectTo(): ?Ref4
    {
        return static::$instance->getRequestRedirectTo();
    }
    public static function getThemeIdFor(string $area): string
    {
        return static::$instance->getThemeIdFor(...func_get_args());
    }
    public static function getThemeMap(): array
    {
        return static::$instance->getThemeMap();
    }
    public static function dispatchChildNode(Ref5 $view, string $name): mixed
    {
        return static::$instance->dispatchChildNode(...func_get_args());
    }
    public static function uri(Ref6|Ref7|string $uri, Ref6|Ref7|string|bool|null $from = null, Ref6|Ref7|string|bool|null $to = null, bool $asRequest = false): Ref7
    {
        return static::$instance->uri(...func_get_args());
    }
    public static function flashMessage(string $id, string $message, string $type = 'info'): void
    {
    }
    public static function getCommsManager(): Ref8
    {
        return static::$instance->getCommsManager();
    }
    public static function loadTemplate(string $path, callable|array|null $slots = null): Ref9
    {
        return static::$instance->loadTemplate(...func_get_args());
    }
    public static function emitEvent(Ref10|string $entity, string $action, ?array $data = null, ?Ref11 $jobQueue = null, ?Ref12 $job = null): void
    {
    }
    public static function dateToString(?Ref13 $date): ?string
    {
        return static::$instance->dateToString(...func_get_args());
    }
    public static function normalizeDate(mixed $date): Ref13
    {
        return static::$instance->normalizeDate(...func_get_args());
    }
    public static function normalizeDuration(mixed $duration): Ref14
    {
        return static::$instance->normalizeDuration(...func_get_args());
    }
    public static function prepareDate(mixed $date): ?Ref15
    {
        return static::$instance->prepareDate(...func_get_args());
    }
    public static function prepareInterval(mixed $interval): ?Ref16
    {
        return static::$instance->prepareInterval(...func_get_args());
    }
    public static function formatDuration(Ref6|Ref14|Ref17|string|int|null $duration, int $maxUnits = 1, bool $shortUnits = false, string|int $maxUnit = 7, bool $roundLastUnit = true): ?string
    {
        return static::$instance->formatDuration(...func_get_args());
    }
    public static function getUserManager(): Ref18
    {
        return static::$instance->getUserManager();
    }
    public static function getClient(): Ref19
    {
        return static::$instance->getClient();
    }
    public static function guid(Ref20|string|null $guid): Ref20
    {
        return static::$instance->guid(...func_get_args());
    }
    public static function newGuid(): Ref20
    {
        return static::$instance->newGuid();
    }
    public static function shortenGuid(Ref20|string $guid): string
    {
        return static::$instance->shortenGuid(...func_get_args());
    }
    public static function unshortenGuid(string $id): string
    {
        return static::$instance->unshortenGuid(...func_get_args());
    }
    public static function getTable(string $id): Ref21
    {
        return static::$instance->getTable(...func_get_args());
    }
    public static function getEnum(string $id): Ref22
    {
        return static::$instance->getEnum(...func_get_args());
    }
    public static function getCache(string $id): Ref23
    {
        return static::$instance->getCache(...func_get_args());
    }
    public static function getConfig(string $id): Ref24
    {
        return static::$instance->getConfig(...func_get_args());
    }
    public static function getRelationId(Ref25|Ref26|array|null $record, string $field, ?string $idField = null): ?string
    {
        return static::$instance->getRelationId(...func_get_args());
    }
    public static function selectForAction(Ref27|string $source, array|string $fields, mixed $primary = null, ?callable $queryChain = null): array
    {
        return static::$instance->selectForAction(...func_get_args());
    }
    public static function queryForAction(Ref28 $query, ?callable $chain = null): array
    {
        return static::$instance->queryForAction(...func_get_args());
    }
    public static function queryByPrimaryForAction(Ref28 $query, mixed $primary, ?callable $chain = null): array
    {
        return static::$instance->queryByPrimaryForAction(...func_get_args());
    }
    public static function normalizeBlock(mixed $block): ?Ref29
    {
        return static::$instance->normalizeBlock(...func_get_args());
    }
    public static function normalizeSlot(mixed $slot): ?Ref30
    {
        return static::$instance->normalizeSlot(...func_get_args());
    }
    public static function sendPreparedMail(string $path, ?array $slots = null, bool $forceSend = false): void
    {
    }
    public static function prepareMail(string $path, ?array $slots = null, bool $forceSend = false): Ref31
    {
        return static::$instance->prepareMail(...func_get_args());
    }
    public static function renderMail(string $path, ?array $slots = null, bool $forceSend = false): string
    {
        return static::$instance->renderMail(...func_get_args());
    }
    public static function launchTask(string $request, ?Ref32 $session = null, ?string $user = null, bool $dfSource = false, ?bool $decoratable = null): Ref33
    {
        return static::$instance->launchTask(...func_get_args());
    }
    public static function launchBackgroundTask(string $request, ?string $user = null, bool $dfSource = false, ?bool $decoratable = null): Ref34
    {
        return static::$instance->launchBackgroundTask(...func_get_args());
    }
    public static function getCurrencyNames(): array
    {
        return static::$instance->getCurrencyNames();
    }
    public static function setRegistryObject(Ref35 $object): void
    {
    }
    public static function getRegistryObject(string $key): ?Ref35
    {
        return static::$instance->getRegistryObject(...func_get_args());
    }
    public static function hasRegistryObject(string $key): bool
    {
        return static::$instance->hasRegistryObject(...func_get_args());
    }
    public static function removeRegistryObject(string $key): void
    {
    }
    public static function findRegistryObjects(string $beginningWith): array
    {
        return static::$instance->findRegistryObjects(...func_get_args());
    }
    public static function getRegistryObjects(): array
    {
        return static::$instance->getRegistryObjects();
    }
}
