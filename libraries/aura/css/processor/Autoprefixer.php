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
use df\flex;

class Autoprefixer extends Base
{
    public function process($cssPath, core\io\IMultiplexer $multiplexer=null)
    {
        $bridge = new spur\node\Bridge();

        if (!$bridge->find('autoprefixer')) {
            try {
                $bridge->npmInstall('autoprefixer', $multiplexer);
            } catch (\Throwable $e) {
                core\log\Manager::getInstance()->logException($e);
                return;
            }
        }

        if (!$bridge->find('postcss')) {
            $bridge->npmInstall('postcss');
        }

        if (!isset($this->settings->cascade)) {
            $this->settings->cascade = true;
        }

        if (!isset($this->settings->remove)) {
            $this->settings->remove = true;
        }

        $content = file_get_contents($cssPath);
        $map = null;

        if (preg_match('/sourceMappingURL\=([^ ]+) \*/i', $content, $matches)) {
            $map = file_get_contents($cssPath.'.map');
        }

        $js =
<<<js
var autoprefixer = require('autoprefixer');
var postcss      = require('postcss');

var output = postcss([ autoprefixer(data.settings) ]).process(data.css, {
    from: data.path,
    to: data.path,
    map: data.map
});

return {
    css: output.css,
    map: output.map
};
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

        core\fs\File::create($cssPath, $output['css']);

        if (isset($output['map'])) {
            core\fs\File::create(
                $cssPath.'.map',
                flex\Json::toString($output['map'], \JSON_UNESCAPED_SLASHES)
            );
        }
    }
}
