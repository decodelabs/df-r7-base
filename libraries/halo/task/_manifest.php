<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\task;

use df;
use df\core;
use df\halo;
use df\arch;

    
// Exceptions
interface IException {}



// Interfaces
interface IResponse extends core\IRegistryObject {
    public function setChannels(array $channels);
    public function addChannels(array $channels);
    public function addChannel(core\io\IChannel $channel);
    public function hasChannel($id);
    public function getChannel($id);
    public function removeChannel($id);
    public function getChannels();
    public function clearChannels();

    public function flush();
    public function write($data);
    public function writeLine($line);
    public function writeError($error);
    public function writeErrorLine($line);
}

