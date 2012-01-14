<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\address;

use df;
use df\core;
use df\halo;


// Interfaces
interface IAddress extends core\uri\IUrl, core\uri\ITransientSchemeUrl {
    public function getSocketDomain();
    public function getDefaultSocketType();
}

interface IInetAddress extends IAddress, core\uri\IIpPortContainer {
    
}

interface IUnixAddress extends IAddress, core\uri\IPathContainer {
    
}