<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\css\processor;

use DecodeLabs\Atlas;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch;
use DecodeLabs\Overpass\Bridge;
use DecodeLabs\Terminus\Session;
use df\flex;

class Autoprefixer extends Base
{
    protected Bridge $bridge;

    public function setup(?Session $session = null)
    {
        $this->bridge = (new Bridge(Genesis::$hub->getLocalDataPath() . '/node', $session))
            ->ensurePackage('autoprefixer')
            ->ensurePackage('postcss');
    }

    public function process($cssPath, ?Session $session = null)
    {
        if (!isset($this->settings->cascade)) {
            $this->settings->cascade = true;
        }

        if (!isset($this->settings->remove)) {
            $this->settings->remove = true;
        }

        if (false === ($content = file_get_contents($cssPath))) {
            throw Exceptional::Runtime(
                'Unable to read css file contents',
                null,
                $cssPath
            );
        }

        $map = null;

        if (preg_match('/sourceMappingURL\=([^ ]+) \*/i', $content, $matches)) {
            $map = file_get_contents($cssPath . '.map');
        }


        $output = $this->bridge->run(__DIR__ . '/autoprefixer.js', [
            'css' => $content,
            'map' => $map ? [
                'prev' => $map,
                'inline' => false
            ] : null,
            'path' => $cssPath,
            'settings' => $this->settings
        ]);

        if (!isset($output)) {
            Glitch::logException(Exceptional::Runtime(
                'Unable to autoprefix css'
            ));
            return;
        }

        Atlas::createFile($cssPath, $output['css']);

        if (isset($output['map'])) {
            Atlas::createFile(
                $cssPath . '.map',
                flex\Json::toString($output['map'], \JSON_UNESCAPED_SLASHES)
            );
        }
    }
}
