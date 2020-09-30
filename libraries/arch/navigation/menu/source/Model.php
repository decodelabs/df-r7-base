<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\menu\source;

use df;
use df\core;
use df\arch;
use df\axis;

use DecodeLabs\Exceptional;

class Model extends Base
{
    public function loadMenu(core\uri\IUrl $id)
    {
        $modelName = $id->path->getFirst();
        $model = axis\Model::factory($modelName);
        $menuId = $id->path->getLast();

        if (!$model instanceof arch\navigation\menu\ISourceAdapter) {
            throw Exceptional::NotFound(
                'Model '.$modelName.' is not a menu source adapter'
            );
        }

        $output = $model->loadMenu($this, $id);

        return $output;
    }
}
