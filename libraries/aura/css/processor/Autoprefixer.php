<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\css\processor;

use df;
use df\core;
use df\aura;
use df\spur;

class Autoprefixer extends Base {

    public function process($cssPath) {
        $bridge = new spur\node\Bridge();

        if(!$bridge->find('autoprefixer')) {
            try {
                $bridge->npmInstall('autoprefixer');
            } catch(\Throwable $e) {
                core\log\Manager::getInstance()->logException($e);
                return;
            }
        }

        if(!$bridge->find('postcss')) {
            $bridge->npmInstall('postcss');
        }

        if(!isset($this->settings->cascade)) {
            $this->settings->cascade = true;
        }

        if(!isset($this->settings->remove)) {
            $this->settings->remove = true;
        }

        $content = file_get_contents($cssPath);
        $map = null;

        if(preg_match('/sourceMappingURL\=([^ ]+) \*/i', $content, $matches)) {
            $map = file_get_contents($cssPath.'.map');
        }

        $js =
<<<js
var autoprefixer = require('autoprefixer');
var postcss      = require('postcss');

return postcss([ autoprefixer(data.settings) ]).process(data.css, {
    from: data.path,
    to: data.path,
    map: data.map
}).css;
js;

        $output = $bridge->evaluate($js, [
            'css' => $content,
            'map' => $map ? [
                'prev' => $map,
                'inline' => false
            ] : null,
            'path' => $cssPath,
            'settings' => $this->settings
        ]);

        file_put_contents($cssPath, $output);
    }
}
