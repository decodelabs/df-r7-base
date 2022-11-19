<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df\arch;
use df\mesh;

class MeshHandler implements mesh\IEntityHandler
{
    public function fetchEntity(mesh\IManager $manager, array $node)
    {
        switch ($node['type']) {
            case 'Context':
                $id = $node['id'];

                if ($id === null) {
                    $id = 'Http';
                }

                $request = arch\Request::factory($id . '://~' . implode('/', $node['location']) . '/');
                return arch\Context::factory($request);
        }
    }
}
