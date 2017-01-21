<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme;

use df;
use df\core;
use df\aura;
use df\flex;
use df\spur;

class Dependency implements IDependency {

    public $id;
    public $version;
    public $source;
    public $js = [];
    public $css = [];
    public $shim;
    public $map;
    public $installName;

    public function __construct($id, $data=null) {
        $parts = explode('#', $id, 2);
        $id = array_shift($parts);
        $version = array_shift($parts);

        if(!is_array($data)) {
            if(false !== strpos($data, '#')) {
                $parts = explode('#', $data, 2);

                $data = [
                    'version' => array_pop($parts),
                    'source' => array_shift($parts)
                ];
            } else if($data == 'latest') {
                $data = ['version' => $data];
            } else {
                try {
                    $version = flex\VersionRange::factory($data);
                    $data = ['version' => $data];
                } catch(\Exception $e) {
                    $data = ['source' => $data];
                }
            }
        }

        $this->id = $id;

        if(isset($data['source'])) {
            $this->source = $data['source'];
        } else {
            $this->source = $id;
        }

        if(isset($data['version'])) {
            $version = $data['version'];
        }

        $this->version = $version;

        if(isset($data['js']) && !empty($data['js'])) {
            $this->js = $data['js'];

            if(!is_array($this->js)) {
                $this->js = [$this->js];
            }
        }

        if(isset($data['css']) && !empty($data['css'])) {
            $this->css = $data['css'];

            if(!is_array($css)) {
                $this->css = [$css];
            }
        }

        if(isset($data['map'])) {
            $this->map = (array)$data['map'];
        }

        if(isset($data['shim'])) {
            $this->shim = $data['shim'];

            if(!is_array($this->shim)) {
                $this->shim = ['exports' => $this->shim];
            }
        }
    }

    public function getId() {
        return $this->id;
    }

    public function getVersion() {
        return $this->version;
    }

    public function getSource() {
        return $this->source;
    }

    public function getJs() {
        return $this->js;
    }

    public function getCss() {
        return $this->css;
    }

    public function getShim() {
        return $this->shim;
    }

    public function getMap() {
        return $this->map;
    }

    public function getKey() {
        return $this->id.'#'.(string)$this->version;
    }

    public function getPackage() {
        return spur\packaging\bower\Package::fromThemeDependency($this);
    }

    public function getInstallName() {
        return $this->installName;
    }
}