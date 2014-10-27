<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\plug;
use df\arch;
    
class Navigation implements arch\IDirectoryHelper {

    use arch\TDirectoryHelper;

    public function getMenu($id) {
        return arch\navigation\menu\Base::factory($this->context, $id);
    }

    public function getBreadcrumbs($empty=false) {
        if(!$output = $this->context->application->getRegistryObject('breadcrumbs')) {
            if($empty) {
                $output = new arch\navigation\breadcrumbs\EntryList();
            } else {
                $output = arch\navigation\breadcrumbs\EntryList::generateFromRequest(
                    $this->context->request
                );
            }
            
            $this->context->application->setRegistryObject($output);
        }

        return $output;
    }

    public function getPageTitle() {
        $breadcrumbs = $this->getBreadcrumbs();

        if($entry = $breadcrumbs->getLastEntry()) {
            return $entry->getBody();
        }

        return $this->context->$application->getName();
    }

    public function clearMenuCache($id=null) {
        if($id !== null) {
            arch\navigation\menu\Base::clearCacheFor($this->context, $id);
        } else {
            arch\navigation\menu\Base::clearCache($this->context);
        }

        return $this;
    }
}