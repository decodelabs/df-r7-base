<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\context;

use df;
use df\core;
use df\plug;
use df\arch;
    
class Navigation implements arch\IContextHelper {

    use arch\TContextHelper;

    public function getMenu($id) {
        return arch\navigation\menu\Base::factory($this->_context, $id);
    }

    public function getBreadcrumbs($empty=false) {
        $application = $this->_context->getApplication();

        if(!$output = $application->getRegistryObject('breadcrumbs')) {
            if($empty) {
                $output = new arch\navigation\breadcrumbs\EntryList();
            } else {
                $output = arch\navigation\breadcrumbs\EntryList::generateFromRequest(
                    $this->_context->request
                );
            }
            
            $application->setRegistryObject($output);
        }

        return $output;
    }

    public function clearMenuCache($id=null) {
        if($id !== null) {
            arch\navigation\menu\Base::clearCacheFor($this->_context, $id);
        } else {
            arch\navigation\menu\Base::clearCache($this->_context);
        }

        return $this;
    }
}