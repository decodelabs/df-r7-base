<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;
use df\mesh;

class MeshHandler implements mesh\IEntityHandler {

    public function fetchEntity(mesh\IManager $manager, array $node) {
        switch($node['type']) {
            case 'Controller':
                $id = $node['id'];

                if($id === null) {
                    $id = 'Http';
                }

                $request = arch\Request::factory($id.'://~'.implode('/', $node['location']).'/');
                $context = arch\Context::factory($request);

                return arch\Controller::factory($context);

            case 'Context':
                $id = $node['id'];

                if($id === null) {
                    $id = 'Http';
                }

                $request = arch\Request::factory($id.'://~'.implode('/', $node['location']).'/');
                return arch\Context::factory($request);
        }
    }
}
