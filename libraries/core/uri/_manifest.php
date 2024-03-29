<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\uri;

use DecodeLabs\Compass\Ip;

use df\core;

interface IPath extends core\IStringProvider, core\collection\IIndexedQueue
{
    public static function normalizeLocal($path): string;
    public static function extractFileName($path);
    public static function extractRootFileName($path);
    public static function extractExtension($path);

    public function setSeparator($separator);
    public function getSeparator();
    public function isAbsolute(bool $flag = null);
    public function shouldAddTrailingSlash(bool $flag = null);
    public function canAutoCanonicalize(bool $flag = null);
    public function canonicalize();
    public function extractRelative($path);
    public function getRawCollection();

    public function getDirname();
    public function setBaseName($baseName);
    public function getBaseName();
    public function setFileName($fileName);
    public function getFileName();
    public function hasExtension(...$extensions);
    public function setExtension($extension);
    public function getExtension();

    public function toUrlEncodedString();

    public function hasWinDrive();
    public function getWinDriveLetter();
}


interface ISecureSchemeContainer
{
    public function isSecure(bool $flag = null);
}

interface IUsernameContainer
{
    public function setUsername($username);
    public function getUsername();
    public function hasUsername(...$usernames);
}

interface ICredentialContainer extends IUsernameContainer
{
    public function setPassword($password);
    public function getPassword();
    public function hasPassword(...$passwords);
    public function setCredentials($username, $password);
    public function hasCredentials();
}

interface IDomainContainer
{
    public function setDomain($domain);
    public function getDomain();
    public function hasDomain();
    public function lookupIp(): Ip;
}

interface IPortContainer
{
    public function setPort($port);
    public function getPort();
    public function hasPort(...$ports);
}

interface IDomainPortContainer extends IDomainContainer, IPortContainer
{
    public function getHost(): string;
}


interface IPathContainer
{
    public function setPath($path);
    public function getPath();
    public function getPathString();
    public function hasPath();
}

interface IFragmentContainer
{
    public function getFragment();
    public function setFragment($fragment);
    public function hasFragment(...$fragments);
    public function isJustFragment();
}

interface IQueryContainer
{
    public function setQuery($query);
    public function importQuery($query, array $filter = null);
    public function getQuery(): core\collection\ITree;
    public function getQueryString(): string;
    public function getQueryTerm($key, $default = null);
    public function hasQuery(): bool;
}

/*****************
 * URL
 */
interface IUrl extends core\IStringProvider
{
    public function import($url = '');

    /**
     * @return $this
     */
    public function reset(): static;

    public function getScheme();
    public function toReadableString();
}

interface ITransientSchemeUrl
{
    public function setScheme($scheme);
    public function hasScheme();
}



interface IGenericUrl extends IUrl, ITransientSchemeUrl, IPathContainer, IQueryContainer, IFragmentContainer
{
}

interface IMailtoUrl extends IUrl, IUsernameContainer, IDomainContainer, IQueryContainer
{
    public function setEmail($email);
    public function getEmail();
    public function hasEmail(...$emails);

    public function setSubject($subject);
    public function getSubject();
    public function hasSubject();
}

interface ITelephoneUrl extends IUrl
{
    public function setNumber($number);
    public function getNumber();
    public function getCanonicalNumber();
}




interface ITemplate
{
    public function expand(array $variables);
}
