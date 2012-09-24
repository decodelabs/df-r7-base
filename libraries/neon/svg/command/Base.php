<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg\command;

use df;
use df\core;
use df\neon;
    
abstract class Base implements neon\svg\ICommand {

	use core\TStringProvider;

	private static $_commandKeys = [
		'm' => 'Move',
		'z' => 'ClosePath',
		'l' => 'Line',
		'h' => 'HorizontalLine',
		'v' => 'VerticalLine',
		'c' => 'CubicCurve',
		's' => 'SmoothCubicCurve',
		'q' => 'QuadraticCurve',
		't' => 'SmoothQuadraticCurve',
		'a' => 'Arc'
	];

	protected $_isRelative = false;

    public static function listFactory($commands) {
    	if(is_string($commands)) {
            $commands = str_replace('  ', ' ', $commands);
			$matches = preg_split('/([a-zA-Z])/', $commands, null, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY);
			$commands = array();

			while(!empty($matches)) {

                do {
				    $command = trim(array_shift($matches));
                } while(empty($command) && !empty($matches));

				if(strtolower($command) != 'z') {
					$command .= array_shift($matches);
                }

                $command = trim($command);

                if(!empty($command)) {
				    $commands[] = $command;
                }
			}
		}

		if(!is_array($commands)) {
			$commands = [$commands];
		}

		foreach($commands as $i => $command) {
			$commands[$i] = self::factory($command);
		}

		return $commands;
    }

    public static function factory($command) {
    	if($command instanceof ICommand) {
    		return $command;
    	}

    	$command = trim(str_replace(['-', ',', '  '], [' -', ' ', ' '], $command));

    	if(in_array(
    		strtolower($command), 
    		[
    			'arc', 'closepath', 'cubiccurve', 'horizontalline', 'line', 'move', 
    			'quadraticcurve', 'smoothcubiccurve', 'smoothquadraticcurve', 'verticalline'
			]
		)) {
    		$command = ucfirst($command);
    		$args = array_slice(func_get_args(), 1);
    	} else if(preg_match('/^([a-zA-Z])([^a-zA-Z]+)?/', $command, $matches)) {
    		$key = strtolower($matches[1]);
    		$isRelative = $key == $matches[1];

    		if(!isset(self::$_commandKeys[$key])) {
    			throw new neon\svg\InvalidArgumentException(
	    			$matches[1].' is not a valid path command key'
				);
    		}

    		$command = self::$_commandKeys[$key];
    		$args = isset($matches[2]) && $matches[1] !== '' ? explode(' ', trim($matches[2])) : array();
    	} else {
    		throw new neon\svg\InvalidArgumentException(
    			$command.' is not a valid path command'
			);
    	}

    	$class = 'df\\neon\\svg\\command\\'.$command;

    	if(!class_exists($class)) {
    		throw new neon\svg\InvalidArgumentException(
    			$command.' command does not appear to exist'
			);
    	}

    	foreach($args as $i => $arg) {
    		$args[$i] = trim($arg);
    	}

    	$ref = new \ReflectionClass($class);
    	$output = $ref->newInstanceArgs($args);
    	$output->isRelative((bool)$isRelative);

    	return $output;
    }


    public function isRelative($flag=null) {
    	if($flag !== null) {
    		$this->_isRelative = (bool)$flag;
    		return $this;
    	}

    	return $this->_isRelative;
    }

    public function isAbsolute($flag=null) {
    	if($flag !== null) {
    		$this->_isRelative = !(bool)$flag;
    		return $this;
    	}

    	return !$this->_isRelative;
    }
}