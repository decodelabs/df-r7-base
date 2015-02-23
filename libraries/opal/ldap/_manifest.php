<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap;

use df;
use df\core;
use df\opal;

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}

class InvalidDnException extends UnexpectedValueException {}
class ConnectionException extends RuntimeException {}
class BindException extends RuntimeException {}
class DomainException extends RuntimeException {}
class QueryException extends RuntimeException {}


// Interfaces
interface ISecurity {
    const NONE = null;
    const SSL = 'ssl';
    const TLS = 'tls';
}

interface ITarget {
    const ENTRY = false;
    const NODE = null;
    const TREE = true;
}

interface IStatus {
    const SUCCESS                        = 0x00;
    const OPERATIONS_ERROR               = 0x01;
    const PROTOCOL_ERROR                 = 0x02;
    const TIMELIMIT_EXCEEDED             = 0x03;
    const SIZELIMIT_EXCEEDED             = 0x04;
    const COMPARE_FALSE                  = 0x05;
    const COMPARE_TRUE                   = 0x06;
    const AUTH_METHOD_NOT_SUPPORTED      = 0x07;
    const STRONG_AUTH_REQUIRED           = 0x08;
    const PARTIAL_RESULTS                = 0x09;
    const REFERRAL                       = 0x0a;
    const ADMINLIMIT_EXCEEDED            = 0x0b;
    const UNAVAILABLE_CRITICAL_EXTENSION = 0x0c;
    const CONFIDENTIALITY_REQUIRED       = 0x0d;
    const SASL_BIND_IN_PROGRESS          = 0x0e;
    const NO_SUCH_ATTRIBUTE              = 0x10;
    const UNDEFINED_TYPE                 = 0x11;
    const INAPPROPRIATE_MATCHING         = 0x12;
    const CONSTRAINT_VIOLATION           = 0x13;
    const TYPE_OR_VALUE_EXISTS           = 0x14;
    const INVALID_SYNTAX                 = 0x15;
    const NO_SUCH_OBJECT                 = 0x20;
    const ALIAS_PROBLEM                  = 0x21;
    const INVALID_DN_SYNTAX              = 0x22;
    const IS_LEAF                        = 0x23;
    const ALIAS_DEREF_PROBLEM            = 0x24;
    const PROXY_AUTHZ_FAILURE            = 0x2F;
    const INAPPROPRIATE_AUTH             = 0x30;
    const INVALID_CREDENTIALS            = 0x31;
    const INSUFFICIENT_ACCESS            = 0x32;
    const BUSY                           = 0x33;
    const UNAVAILABLE                    = 0x34;
    const UNWILLING_TO_PERFORM           = 0x35;
    const LOOP_DETECT                    = 0x36;
    const NAMING_VIOLATION               = 0x40;
    const OBJECT_CLASS_VIOLATION         = 0x41;
    const NOT_ALLOWED_ON_NONLEAF         = 0x42;
    const NOT_ALLOWED_ON_RDN             = 0x43;
    const ALREADY_EXISTS                 = 0x44;
    const NO_OBJECT_CLASS_MODS           = 0x45;
    const RESULTS_TOO_LARGE              = 0x46;
    const AFFECTS_MULTIPLE_DSAS          = 0x47;
    const OTHER                          = 0x50;
    const SERVER_DOWN                    = 0x51;
    const LOCAL_ERROR                    = 0x52;
    const ENCODING_ERROR                 = 0x53;
    const DECODING_ERROR                 = 0x54;
    const TIMEOUT                        = 0x55;
    const AUTH_UNKNOWN                   = 0x56;
    const FILTER_ERROR                   = 0x57;
    const USER_CANCELLED                 = 0x58;
    const PARAM_ERROR                    = 0x59;
    const NO_MEMORY                      = 0x5a;
    const CONNECT_ERROR                  = 0x5b;
    const NOT_SUPPORTED                  = 0x5c;
    const CONTROL_NOT_FOUND              = 0x5d;
    const NO_RESULTS_RETURNED            = 0x5e;
    const MORE_RESULTS_TO_RETURN         = 0x5f;
    const CLIENT_LOOP                    = 0x60;
    const REFERRAL_LIMIT_EXCEEDED        = 0x61;
    const CUP_RESOURCES_EXHAUSTED        = 0x71;
    const CUP_SECURITY_VIOLATION         = 0x72;
    const CUP_INVALID_DATA               = 0x73;
    const CUP_UNSUPPORTED_SCHEME         = 0x74;
    const CUP_RELOAD_REQUIRED            = 0x75;
    const CANCELLED                      = 0x76;
    const NO_SUCH_OPERATION              = 0x77;
    const TOO_LATE                       = 0x78;
    const CANNOT_CANCEL                  = 0x79;
    const ASSERTION_FAILED               = 0x7A;
    const SYNC_REFRESH_REQUIRED          = 0x1000;
    const X_SYNC_REFRESH_REQUIRED        = 0x4100;
    const X_NO_OPERATION                 = 0x410e;
    const X_ASSERTION_FAILED             = 0x410f;
    const X_NO_REFERRALS_FOUND           = 0x4110;
    const X_CANNOT_CHAIN                 = 0x4111;
}

interface IDn extends core\collection\IIndexedQueue, core\IStringProvider {
    public function implode($separator=',', $case=core\string\ICase::NONE);
    public function isChildOf($dn);
    public function getFirstEntry($key);
    public function getAllEntries($key);
    public function buildDomain();
}


interface IRdn extends \Countable, core\collection\IAttributeContainer, core\IStringProvider {
    public function implode($case=core\string\ICase::NONE);
    public function eq($rdn);
}

interface IConnection {

    const ACTIVE_DIRECTORY = 'ActiveDirectory';
    const OPEN_LDAP = 'OpenLdap';
    const EDIRECTORY = 'EDirectory';
    const GENERIC = null;

    public function getHost();
    public function getPort();
    public function getConnectionString();
    public function getEncryption();
    public function hasEncryption();
    public function usesSsl();
    public function usesTls();
    public function isConnected();
    public function getResource();
    public function connect();
    public function disconnect();
    public function bind($username, $password);
    public function bindIdentity(IIdentity $identity, IContext $context);
    public function isBound();
}


interface IContext {
    public function setBaseDn($baseDn);
    public function getBaseDn();
    public function getDomain();
    public function setUpnDomain($domain=null);
    public function getUpnDomain();
}

interface IIdentity {
    public function setUsername($username);
    public function getUsername();
    public function getPreparedUsername(IConnection $connection, IContext $context);
    public function setPassword($password);
    public function getPassword();
    public function setDomain($domain, $type=null);
    public function getDomain();
    public function getDomainType();
    public function hasUidDomain();
    public function hasUpnDomain();
    public function hasDnDomain();
}

interface IAdapter extends opal\query\IAdapter, opal\query\IEntryPoint {
    public static function getArrayAttributes();
    public static function getDateAttributes();
    public static function getBooleanAttributes();
    public static function getBinaryAttributes();

    public function getConnection();
    public function setContext($context);
    public function getContext();

    public function setPrivilegedIdentity(IIdentity $identity=null);
    public function getPrivilegedIdentity();

    public function isBound();
    public function getBoundIdentity();
    public function bind(IIdentity $identity);
    public function ensureBind();

    public function fetchRootDse();
}

interface IRootDse extends core\collection\IMappedCollection {
    public function getNamingContexts();
    public function getSubschemaSubentry();
    public function supportsVersion($version);
    public function supportsSaslMechanism($mechanism);
    public function getSchemaDn();
}

interface IRecord extends opal\record\ILocationalRecord, core\collection\IAttributeContainer {
    public function getEntryDn();
    public function getGlobalId();
    public function getObjectClasses();
    public function inside($location);
}
