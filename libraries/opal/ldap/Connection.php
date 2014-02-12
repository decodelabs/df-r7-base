<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap;

use df;
use df\core;
use df\opal;

class Connection implements IConnection {
    
    protected $_host;
    protected $_port;
    protected $_encryption;
    protected $_type;
    protected $_connectionString;
    protected $_connection;
    protected $_bind;

    public static function factory($host, $port=null, $encryption=ISecurity::NONE, $type=IConnection::GENERIC) {
        if($host instanceof IConnection) {
            return $host;
        }

        return new self($host, $port, $encryption, $type);
    }

    public function __construct($host, $port=null, $encryption=ISecurity::NONE, $type=IConnection::GENERIC) {
        if(is_array($host)) {
            $port = isset($host['port']) ? $host['port'] : $port;
            $encryption = isset($host['encryption']) ? $host['encryption'] : $encryption;
            $type = isset($host['type']) ? $host['type'] : $type;

            if(!isset($host['host'])) {
                throw new ConnectionException(
                    'Host not set'
                );
            }

            $host = $host['host'];
        }

        if(empty($port)) {
            $port = null;
        }

        if(empty($encryption)) {
            $encryption = null;
        }

        if(empty($type)) {
            $type = null;
        }

        $this->_host = $host;
        $this->_port = $port;

        switch($encryption) {
            case ISecurity::SSL:
            case ISecurity::TLS:
            case ISecurity::NONE:
                $this->_encryption = $encryption;
                break;
                
            default:
                $this->_encryption = ISecurity::NONE;
                break;
        }
        
        $this->setType($type);
    }

    public function __destruct() {
        $this->disconnect();
    }
    
    public function getHost() {
        return $this->_host;
    }
    
    public function getPort() {
        return $this->_port;
    }

    public function getConnectionString() {
        return $this->_connectionString;
    }
    
    public function getEncryption() {
        return $this->_encryption;
    }
    
    public function hasEncryption() {
        return $this->_encryption != ISecurity::NONE;
    }
    
    public function usesSsl() {
        return $this->_encryption == ISecurity::SSL;
    }
    
    public function usesTls() {
        return $this->_encryption == ISecurity::TLS;
    }
    
    public function getHash() {
        return md5($this->_connectionString);
    }
    
    public function setType($type) {
        if(is_string($type)) {
            $type = strtolower($type);
        }
        
        switch($type) {
            case IConnection::ACTIVE_DIRECTORY:
            case IConnection::OPEN_LDAP:
            case IConnection::EDIRECTORY:
                $this->_type = $type;
                break;
                
            default:
                $this->_type = IConnection::GENERIC;
                break;
        }
        
        return $this;
    }
    
    public function getType() {
        if($this->_type === null) {
            $this->_type = $this->_detectType();
        }

        if($this->_type === false) {
            return null;
        }

        return $this->_type;
    }

    protected function _detectType() {
        $this->connect();
        $result = ldap_read($this->_connection, '', '(objectclass=*)', ['*', '+']);

        if(!$result) {
            throw new ConnectionException(
                'Could not read rootDse'
            );
        }

        $rowEntry = ldap_first_entry($this->_connection, $result);
        $row = ldap_get_attributes($this->_connection, $rowEntry);

        if(isset($row['domainFunctionality'])) {
            return IConnection::ACTIVE_DIRECTORY;
        } else if(isset($row['dsaName'])) {
            return IConnection::EDIRECTORY;
        } else if(isset($row['structuralObjectClass']) && $row['structuralObjectClass'][0] == 'OpenLDAProotDSE') {
            return IConnection::OPEN_LDAP;
        } else {
            return false;
        }
    }
    
    public function isConnected() {
        return $this->_connection !== null;
    }
    
    public function getResource() {
        if(!$this->isConnected()) {
            $this->connect();
        }
        
        return $this->_connection;
    }



    public function connect() {
        if($this->isConnected()) {
            return $this;
        }

        $useUri = false;
        $matches = array();
        
        $useSsl = $this->usesSsl();
        $useTls = $this->usesTls();
        
        if(preg_match_all('~ldap(?:i|s)?://~', $this->_host, $matches, PREG_SET_ORDER)) {
            $this->_connectionString = $this->_host;
            $useUri = true;
            $useSsl = false;
        } else {
            if($useSsl) {
                $this->_connectionString = 'ldaps://'.$this->_host;
                $useUri = true;
            } else {
                $this->_connectionString = 'ldap://'.$this->_host;
            }
            
            if($this->_port !== null) {
                $this->_connectionString .= ':'.$this->_port;
            }
        }
        
        
        $connection = $useUri ?
            ldap_connect($this->_connectionString) :
            ldap_connect($this->_host, $this->_port);
            
        if(!is_resource($connection)) {
            throw new ConnectionException(
                'Failed to connect to ldap server: '.$this->_connectionString
            );
        }
        
        $this->_connection = $connection;
        
        ldap_set_option($this->_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->_connection, LDAP_OPT_REFERRALS, 0);
        
        if($useTls) {
            ldap_start_tls($this->_connection);
        }
        
        return $this;
    }
    
    public function disconnect() {
        if(is_resource($this->_connection)) {
            ldap_unbind($this->_connection);
        }
        
        $this->_connection = null;
        return $this;
    }
    
    public function bind($username, $password) {
        if(!$this->isConnected()) {
            $this->connect();
        }

        $this->_bind = @ldap_bind($this->_connection, $username, $password);
        
        if(!$this->_bind) {
            $this->_bind = null;
            
            throw new BindException(
                'Unable to bind to ldap connection: '.$this->_connectionString.' - '.$this->getLastErrorMessage(),
                $this->getLastErrorCode()
            );
        }
        
        return $this;
    }

    public function bindIdentity(IIdentity $identity, IContext $context) {
        return $this->bind(
            $identity->getPreparedUsername($this, $context), 
            $identity->getPassword()
        );
    }
    
    public function isBound() {
        return $this->_bind !== null;
    }
    
    
    
    public function getLastErrorCode() {
        $return = @ldap_get_option($this->_connection, LDAP_OPT_ERROR_NUMBER, $err);
        
        if($return === true) {
            if($err <= -1 && $err >= -17) {
                $err = IStatus::SERVER_DOWN + (-$err - 1);
            }
            
            return $err;
        }
        
        return 0;
    }
    
    public function getLastErrorMessage() {
        $code = $this->getLastErrorCode();
        $messages = array();
        
        $errorString1 = @ldap_error($this->_connection);
        
        if($code !== 0 && $errorString1 === 'Success') {
            $errorString1 = @ldap_err2str($code);
        }
        
        if(!empty($errorString1)) {
            $messages[] = $errorString1;
        }
        
        @ldap_get_option($this->_connection, LDAP_OPT_ERROR_STRING, $errorString2);
        
        if(!empty($errorString2) && !in_array($errorString2, $messages)) {
            $messages[] = $errorString2;
        }
        
        $output = '';
        
        if(count($messages)) {
            $output .= implode('; ', $messages);
        }
        
        return $output;
    }
}