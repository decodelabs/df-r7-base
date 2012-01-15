<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\test\core\collection;

use df;
use df\core;
use df\acid;

class SetBatch extends acid\batch\Base {
    
    public function testEmpty() {
        $set = new core\collection\Set();
        $this->assertEmpty($set);
        
        return $set;
    }
    
    /**
     * @depends testEmpty
     */
    public function testAdd(core\collection\Set $set) {
        $set->add('one', 'two', 'one');
        
        $this->assertNotEmpty($set);
        $this->assertEquals(['one', 'two'], $set->toArray());
        
        return $set;
    }
    
    /**
     * @depends testAdd
     */
    public function testHas(core\collection\Set $set) {
        $this->assertTrue($set->has('one'));
        $this->assertTrue($set->has('two', 'three'));
        $this->assertFalse($set->has('three'));
        
        return $set;
    }
}
