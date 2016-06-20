<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf;

use df;
use df\core;
use df\neon;

class AppClass implements IAppClass {

    use core\TStringProvider;

    protected $_dxfName;
    protected $_className;
    protected $_appName;
    protected $_proxyCapabilities;
    protected $_wasProxy = false;
    protected $_isEntity = false;

    public function __construct($dxfName, $className, $appName) {
        $this->setDxfName($dxfName);
        $this->setClassName($className);
        $this->setAppName($appName);
    }

    public function setDxfName($name) {
        $this->_dxfName = strtoupper($name);
        return $this;
    }

    public function getDxfName() {
        return $this->_dxfName;
    }

    public function setClassName($name) {
        $this->_className = $name;
        return $this;
    }

    public function getClassName() {
        return $this->_className;
    }

    public function setAppName($name) {
        $this->_appName = $name;
        return $this;
    }

    public function getAppName() {
        return $this->_appName;
    }

    public function setProxyCapabilities($flag) {
        $this->_proxyCapabilities = (int)$flag;
        return $this;
    }

    public function getProxyCapabilities() {
        return $this->_proxyCapabilities;
    }

    public function hasProxyCapability($flag) {
        return $this->_proxyCapabilities & $flag == $flag;
    }

    public function wasProxy(bool $flag=null) {
        if($flag !== null) {
            $this->_wasProxy = $flag;
            return $this;
        }

        return $this->_wasProxy;
    }

    public function isEntity(bool $flag=null) {
        if($flag !== null) {
            $this->_isEntity = $flag;
            return $this;
        }

        return $this->_isEntity;
    }

    public function toString(): string {
        return sprintf(
            " 0\nCLASS\n 1\n%s\n 2\n%s\n 3\n%s\n 90\n%u\n 280\n%u\n 281\n%u\n",
            $this->_dxfName,
            $this->_className,
            $this->_appName,
            $this->_proxyCapabilities,
            $this->_wasProxy,
            $this->_isEntity
        );
    }
}