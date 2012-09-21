<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\shape;

use df;
use df\core;
use df\neon;
    
class Path implements neon\svg\IPath, core\IDumpable {

	use neon\svg\TAttributeModule;
	use neon\svg\TAttributeModule_Shape;
	use neon\svg\TAttributeModule_PathData;

	public function __construct($commands) {
		$this->setCommands($commands);
	}

// Dump
	public function getDumpProperties() {
		return $this->_attributes;
	}
}