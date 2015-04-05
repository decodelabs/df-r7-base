<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\uri;

use df;
use df\core;

// Exceptions
interface IException {}
class OutOfBoundsException extends \OutOfBoundsException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}

// Interfaces
interface IPath extends core\IStringProvider, core\collection\IIndexedQueue {
    public function setSeparator($separator);
    public function getSeparator();
    public function isAbsolute($flag=null);
    public function shouldAddTrailingSlash($flag=null);
    public function canAutoCanonicalize($flag=null);
    public function canonicalize();
    public function extractRelative($path);
    public function getRawCollection();
    
    public function getDirname();
    public function setBaseName($baseName);
    public function getBaseName();
    public function setFileName($fileName);
    public function getFileName();
    public function hasExtension($extensions=false);
    public function setExtension($extension);
    public function getExtension();
    
    public function toUrlEncodedString();
}

interface IFilePath extends IPath {
    public function hasWinDrive();
    public function getWinDriveLetter();
}


interface ISecureSchemeContainer {
    public function isSecure($flag=null);
}

interface IUsernameContainer {
    public function setUsername($username);
    public function getUsername();
    public function hasUsername($usernames=false);
}

interface ICredentialContainer extends IUsernameContainer {
    public function setPassword($password);
    public function getPassword();
    public function hasPassword($passwords=false);
    public function setCredentials($username, $password);
    public function hasCredentials();
}

interface IDomainContainer {
    public function setDomain($domain);
    public function getDomain();
    public function hasDomain();
    public function lookupIp();
}


interface IIpContainer {
    public function setIp($ip);
    public function getIp();
}

interface IPortContainer {
    public function setPort($port);
    public function getPort();
    public function hasPort($ports=false);
}

interface IDomainPortContainer extends IDomainContainer, IPortContainer {}
interface IIpPortContainer extends IIpContainer, IPortContainer {}

interface IPathContainer {
    public function setPath($path);
    public function getPath();
    public function getPathString();
    public function hasPath();
}

interface IFragmentContainer {
    public function getFragment();
    public function setFragment($fragment);
    public function hasFragment($fragments=false);
    public function isJustFragment();
}

interface IQueryContainer {
    public function setQuery($query);
    public function importQuery($query, array $filter=null);
    public function getQuery();
    public function getQueryString();
    public function getQueryTerm($key, $default=null);
    public function hasQuery();
    public function shouldEncodeQueryAsRfc3986($flag=null);
}

/*****************
 * URL
 */
interface IUrl extends core\IStringProvider {
    public function import($url='');
    public function reset();
    
    public function getScheme();
    public function toReadableString();
}

interface ITransientSchemeUrl {
    public function setScheme($scheme);
    public function hasScheme();
}



interface IGenericUrl extends IUrl, ITransientSchemeUrl, IPathContainer, IQueryContainer, IFragmentContainer {}

interface IMailtoUrl extends IUrl, IUsernameContainer, IDomainContainer, IQueryContainer {
    public function setEmail($email);
    public function getEmail();
    public function hasEmail($emails=false);
    
    public function setSubject($subject);
    public function getSubject();
    public function hasSubject();
}