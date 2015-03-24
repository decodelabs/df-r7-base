<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\breadcrumbs;

use df;
use df\core;
use df\arch;

    
class EntryList implements arch\navigation\IEntryList, core\IRegistryObject {

    use arch\navigation\TEntryList;

    const REGISTRY_KEY = 'breadcrumbs';

    public function getRegistryObjectKey() {
        return self::REGISTRY_KEY;
    }

    public function onApplicationShutdown() {}


    public static function generateFromRequest(arch\IRequest $request) {
        $output = new self();
        $parts = $request->getLiteralPathArray();
        $path = '';

        if(false !== strpos($last = array_pop($parts), '.')) {
            $lastParts = explode('.', $last);
            $last = array_shift($lastParts);
        }

        $parts[] = $last;

        if($request->isDefaultArea()) {
            array_shift($parts);
        }
        
        $isDefaultAction = false;
        
        if($request->isDefaultAction()) {
            array_pop($parts);
            $isDefaultAction = true;
        }
        
        $count = count($parts);
        
        foreach($parts as $i => $part) {
            if(!$isDefaultAction && $i == $count - 1) {
                $path .= $part;
            } else {
                $path .= $part.'/';
            }
            
            $title = $part;
            
            if($i == 0) {
                $title = ltrim($title, $request::AREA_MARKER);
            }
            
            $title = ucwords(
                preg_replace('/([A-Z])/u', ' $1', str_replace(
                    ['-', '_'], ' ', $title
                ))
            );
            
            if($i == $count - 1) {
                $linkRequest = $request;
            } else {
                $linkRequest = $request::factory($path);
            }
            
            $output->addEntry(
                (new arch\navigation\entry\Link($linkRequest, $title))
                    ->setWeight(($i + 1) * 10)
            );
        }

        return $output;
    }
}