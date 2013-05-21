<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris;

use df;
use df\core;
use df\iris;
    
abstract class Translator implements ITranslator {

    public $unit;

    public function __construct($unit) {
        if($unit instanceof iris\IParser) {
            if(!$unit->hasRun()) {
                $unit->parse();
            }

            $unit = $unit->getUnit();
        }

        if(!$unit instanceof iris\map\IUnit) {
            throw new InvalidArgumentException(
                'Invalid unit passed to translator'
            );
        }

        $this->unit = $unit;
    }
}