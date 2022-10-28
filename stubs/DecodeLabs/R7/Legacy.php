<?php
/**
 * This is a stub file for IDE compatibility only.
 * It should not be included in your projects.
 */
namespace DecodeLabs\R7;

use DecodeLabs\Veneer\Proxy;
use DecodeLabs\Veneer\ProxyTrait;
use DecodeLabs\R7\Legacy\Helper as Inst;
use DecodeLabs\R7\Legacy\Plugins\Http as HttpPlugin;
use DecodeLabs\Veneer\Plugin\Wrapper as PluginWrapper;
use df\core\app\Base as Ref0;
use df\core\loader\Base as Ref1;
use df\arch\Context as Ref2;
use df\core\collection\Tree as Ref3;
use df\arch\Request as Ref4;
use df\aura\view\IHtmlView as Ref5;
use df\core\uri\IUrl as Ref6;
use df\flow\Manager as Ref7;
use df\aura\view\content\Template as Ref8;
use df\mesh\job\IQueue as Ref9;
use df\mesh\job\IJob as Ref10;
use df\core\time\Date as Ref11;
use df\core\time\Duration as Ref12;
use Carbon\Carbon as Ref13;
use Carbon\CarbonInterval as Ref14;
use df\user\Manager as Ref15;
use df\user\IClientDataObject as Ref16;
use df\flex\Guid as Ref17;
use df\axis\unit\Table as Ref18;
use df\axis\unit\Enum as Ref19;
use df\axis\unit\Cache as Ref20;
use df\axis\unit\Config as Ref21;
use df\opal\query\ISelectQuery as Ref22;
use DecodeLabs\R7\Nightfire\Block as Ref23;
use df\fire\ISlotContent as Ref24;
use df\arch\mail\Base as Ref25;
use DecodeLabs\Terminus\Session as Ref26;
use DecodeLabs\Systemic\Process\Result as Ref27;
use DecodeLabs\Systemic\Process as Ref28;
use df\core\IRegistryObject as Ref29;

class Legacy implements Proxy
{
    use ProxyTrait;

    const VENEER = 'DecodeLabs\R7\Legacy';
    const VENEER_TARGET = Inst::class;

    public static Inst $instance;
    /** @var HttpPlugin|PluginWrapper<HttpPlugin> $http */
    public static HttpPlugin|PluginWrapper $http;

    public static function app(): Ref0 {
        return static::$instance->app();
    }
    public static function getLoader(): Ref1 {
        return static::$instance->getLoader();
    }
    public static function getPassKey(): string {
        return static::$instance->getPassKey();
    }
    public static function getUniquePrefix(): string {
        return static::$instance->getUniquePrefix();
    }
    public static function setActiveContext(Ref2 $context): void {}
    public static function getActiveContext(): ?Ref2 {
        return static::$instance->getActiveContext();
    }
    public static function getContext(): Ref2 {
        return static::$instance->getContext();
    }
    public static function getQueryVar(string $key): ?string {
        return static::$instance->getQueryVar(...func_get_args());
    }
    public static function hasQueryVar(string $key): bool {
        return static::$instance->hasQueryVar(...func_get_args());
    }
    public static function queryVarExists(string $key): bool {
        return static::$instance->queryVarExists(...func_get_args());
    }
    public static function getQuery(): Ref3 {
        return static::$instance->getQuery();
    }
    public static function getRequest(): Ref4 {
        return static::$instance->getRequest();
    }
    public static function requestMatches(string $pattern): bool {
        return static::$instance->requestMatches(...func_get_args());
    }
    public static function getRequestRedirectFrom(): ?Ref4 {
        return static::$instance->getRequestRedirectFrom();
    }
    public static function getRequestRedirectTo(): ?Ref4 {
        return static::$instance->getRequestRedirectTo();
    }
    public static function dispatchChildNode(Ref5 $view, string $name){
        return static::$instance->dispatchChildNode(...func_get_args());
    }
    public static function uri($uri, $from = NULL, $to = NULL, bool $asRequest = false): Ref6 {
        return static::$instance->uri(...func_get_args());
    }
    public static function flashMessage(string $id, string $message, string $type = 'info'): void {}
    public static function getCommsManager(): Ref7 {
        return static::$instance->getCommsManager();
    }
    public static function loadTemplate(string $path, $slots = NULL): Ref8 {
        return static::$instance->loadTemplate(...func_get_args());
    }
    public static function emitEvent($entity, string $action, ?array $data = NULL, ?Ref9 $jobQueue = NULL, ?Ref10 $job = NULL): void {}
    public static function dateToString(?Ref11 $date): ?string {
        return static::$instance->dateToString(...func_get_args());
    }
    public static function normalizeDate(mixed $date): Ref11 {
        return static::$instance->normalizeDate(...func_get_args());
    }
    public static function normalizeDuration(mixed $duration): Ref12 {
        return static::$instance->normalizeDuration(...func_get_args());
    }
    public static function prepareDate(mixed $date): ?Ref13 {
        return static::$instance->prepareDate(...func_get_args());
    }
    public static function prepareInterval(mixed $interval): ?Ref14 {
        return static::$instance->prepareInterval(...func_get_args());
    }
    public static function formatDuration($duration, int $maxUnits = 1, bool $shortUnits = false, $maxUnit = 7, bool $roundLastUnit = true): ?string {
        return static::$instance->formatDuration(...func_get_args());
    }
    public static function getUserManager(): Ref15 {
        return static::$instance->getUserManager();
    }
    public static function getClient(): Ref16 {
        return static::$instance->getClient();
    }
    public static function guid($guid): Ref17 {
        return static::$instance->guid(...func_get_args());
    }
    public static function newGuid(): Ref17 {
        return static::$instance->newGuid();
    }
    public static function shortenGuid($guid): string {
        return static::$instance->shortenGuid(...func_get_args());
    }
    public static function unshortenGuid(string $id): string {
        return static::$instance->unshortenGuid(...func_get_args());
    }
    public static function getTable(string $id): Ref18 {
        return static::$instance->getTable(...func_get_args());
    }
    public static function getEnum(string $id): Ref19 {
        return static::$instance->getEnum(...func_get_args());
    }
    public static function getCache(string $id): Ref20 {
        return static::$instance->getCache(...func_get_args());
    }
    public static function getConfig(string $id): Ref21 {
        return static::$instance->getConfig(...func_get_args());
    }
    public static function getRelationId($record, string $field, ?string $idField = NULL): ?string {
        return static::$instance->getRelationId(...func_get_args());
    }
    public static function selectForAction($source, $fields, $primary = NULL, ?callable $queryChain = NULL): array {
        return static::$instance->selectForAction(...func_get_args());
    }
    public static function queryForAction(Ref22 $query, ?callable $chain = NULL): array {
        return static::$instance->queryForAction(...func_get_args());
    }
    public static function queryByPrimaryForAction(Ref22 $query, $primary, ?callable $chain = NULL): array {
        return static::$instance->queryByPrimaryForAction(...func_get_args());
    }
    public static function normalizeBlock($block): ?Ref23 {
        return static::$instance->normalizeBlock(...func_get_args());
    }
    public static function normalizeSlot($slot): ?Ref24 {
        return static::$instance->normalizeSlot(...func_get_args());
    }
    public static function sendPreparedMail(string $path, ?array $slots = NULL, bool $forceSend = false): void {}
    public static function prepareMail(string $path, ?array $slots = NULL, bool $forceSend = false): Ref25 {
        return static::$instance->prepareMail(...func_get_args());
    }
    public static function renderMail(string $path, ?array $slots = NULL, bool $forceSend = false): string {
        return static::$instance->renderMail(...func_get_args());
    }
    public static function launchTask(string $request, ?Ref26 $session = NULL, ?string $user = NULL, bool $dfSource = false, ?bool $decoratable = NULL): Ref27 {
        return static::$instance->launchTask(...func_get_args());
    }
    public static function launchBackgroundTask(string $request, ?string $user = NULL, bool $dfSource = false, ?bool $decoratable = NULL): Ref28 {
        return static::$instance->launchBackgroundTask(...func_get_args());
    }
    public static function getCurrencyNames(): array {
        return static::$instance->getCurrencyNames();
    }
    public static function setRegistryObject(Ref29 $object): void {}
    public static function getRegistryObject(string $key): ?Ref29 {
        return static::$instance->getRegistryObject(...func_get_args());
    }
    public static function hasRegistryObject(string $key): bool {
        return static::$instance->hasRegistryObject(...func_get_args());
    }
    public static function removeRegistryObject(string $key): void {}
    public static function findRegistryObjects(string $beginningWith): array {
        return static::$instance->findRegistryObjects(...func_get_args());
    }
    public static function getRegistryObjects(): array {
        return static::$instance->getRegistryObjects();
    }
};
