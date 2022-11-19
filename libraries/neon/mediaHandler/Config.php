<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\neon\mediaHandler;

use DecodeLabs\R7\Legacy;

use df\core;

class Config extends core\Config implements IConfig
{
    public const ID = 'Media';

    public function getDefaultValues(): array
    {
        return [
            'defaultHandler' => 'Local',
            'handlers' => $this->_getDefaultHandlerConfig()
        ];
    }

    protected function _getDefaultHandlerConfig()
    {
        $output = [];

        foreach (Legacy::getLoader()->lookupClassList('neon/mediaHandler') as $name => $class) {
            if ($name == 'Config') {
                continue;
            }

            $conf = $class::getDefaultConfig();
            $conf['enabled'] = $name == 'Local';

            $output[$name] = $conf;
        }

        return $output;
    }

    public function setDefaultHandler($handler)
    {
        if ($handler instanceof IMediaHandler) {
            $handler = $handler->getName();
        }

        $this->values['defaultHandler'] = $handler;
        return $this;
    }

    public function getDefaultHandler()
    {
        return $this->values->get('defaultHandler', 'Local');
    }

    public function setSettingsFor($handler, array $settings)
    {
        if ($handler instanceof IMediaHandler) {
            $handler = $handler->getName();
        }

        if (!isset($settings['enabled'])) {
            $settings['enabled'] = (bool)$this->values->handlers->{$handler}->get('enabled', false);
        }

        $this->values->handlers->{$handler} = $settings;
        return $this;
    }

    public function getSettingsFor($handler)
    {
        if ($handler instanceof IMediaHandler) {
            $handler = $handler->getName();
        }

        return $this->values->handlers->{$handler};
    }

    public function getEnabledHandlers()
    {
        $output = [];

        foreach ($this->values->handlers as $name => $settings) {
            if ($settings['enabled']) {
                $output[] = $name;
            }
        }

        return $output;
    }
}
