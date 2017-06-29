<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\mediaHandler;

use df;
use df\core;
use df\neon;
use df\opal;

abstract class Base implements IMediaHandler {

    const REGISTRY_PREFIX = 'manager://mediaHandler';

    use core\TManager;

    public static function factory($name) {
        $class = 'df\\neon\\mediaHandler\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw new RuntimeException(
                'Media handler '.$name.' could not be found'
            );
        }

        $output = new $class();

        if(!$output instanceof IMediaHandler) {
            throw new RuntimeException(
                'Media handler name '.$name.' did not produce a valid media handler object'
            );
        }

        return $output;
    }

    protected static function _getDefaultInstance() {
        $config = Config::getInstance();
        return self::factory($config->getDefaultHandler());
    }

    public static function getEnabledHandlerList() {
        $config = Config::getInstance();
        $output = [];

        foreach($config->getEnabledHandlers() as $name) {
            $class = 'df\\neon\\mediaHandler\\'.ucfirst($name);

            if(!class_exists($class)) {
                continue;
            }

            $output[$name] = $class::getDisplayName();
        }

        return $output;
    }

    public function getName(): string {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function transferFile($fileId, $versionId, $isActive, $filePath, $fileName) {
        return $this->publishFile($fileId, null, $versionId, $filePath, $fileName);
    }

    public function getEmbedUrl($fileId) {
        return $this->getDownloadUri($fileId);
    }

    public function getImageUrl($fileId, $transformation=null) {
        $output = '/media/image?file='.$this->_normalizeId($fileId);

        if($transformation !== null) {
            $output .= '&transform='.$transformation;
        }

        if(df\Launchpad::$compileTimestamp) {
            $output .= '&cts='.df\Launchpad::$compileTimestamp;
        }

        return $output;
    }

    public function getVersionImageUrl($fileId, $versionId, $isActive, $transformation=null) {
        $output = '/media/image?version='.$this->_normalizeId($versionId);

        if($transformation !== null) {
            $output .= '&transform='.$transformation;
        }

        return $output;
    }

    public static function getDefaultConfig() {
        return [];
    }

    protected function _getSettings() {
        return Config::getInstance()->getSettingsFor($this);
    }

    protected function _normalizeId($id) {
        if($id instanceof opal\record\IPrimaryKeySetProvider) {
            $id = $id->getPrimaryKeySet();
        } else if(is_array($id)) {
            if(isset($id['id'])) {
                $id = $id['id'];
            } else {
                foreach($id as $value) {
                    $id = $value;
                    break;
                }
            }
        }

        return (string)$id;
    }

    protected function _getStorageKey($fileId) {
        if(empty($fileId)) {
            return null;
        }

        $fileId = str_replace('-', '', (string)$fileId);
        $k1 = hexdec($fileId{0});
        $k2 = hexdec($fileId{1});
        $k3 = hexdec($fileId{2});
        return $fileId{$k3}.$fileId{$k2}.$fileId{$k1};
    }
}
