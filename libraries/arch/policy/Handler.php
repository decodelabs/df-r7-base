<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\policy;

use df;
use df\core;
use df\arch;
use df\mesh;

class Handler implements core\policy\IEntityHandler {
    
    public function fetchEntity(core\policy\IManager $manager, mesh\entity\ILocatorNode $node) {
        switch($node->getType()) {
            case 'Controller':
                $id = $node->getId();
            
                if($id === null) {
                    $id = 'Http';
                }
                
                $request = arch\Request::factory($id.'://~'.$node->getLocation().'/');
                $context = arch\Context::factory($manager->getApplication(), $request);
                
                try {
                    return arch\Controller::factory($context);
                } catch(arch\IException $e) {
                    throw new mesh\entity\EntityNotFoundException($e->getMessage());
                }
                
            case 'Context':
                $id = $node->getId();
            
                if($id === null) {
                    $id = 'Http';
                }
                
                $request = arch\Request::factory($id.'://~'.$node->getLocation().'/');
                return arch\Context::factory($manager->getApplication(), $request);
                
            default:
                $class = 'df\\arch\\policy\\'.$type.'Entity';
                
                if(class_exists($class)) {
                    return new $class($node);
                }
                
                return null;
        }
    }
}
