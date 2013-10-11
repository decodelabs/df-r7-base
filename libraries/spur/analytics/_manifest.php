<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\analytics;

use df;
use df\core;
use df\spur;
use df\aura;
    

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}



// Interfaces
interface IHandler {
    public function apply(aura\view\IHtmlView $view);

    public function addConfigAdapters();
    public function addAdapter(IAdapter $adapter);
    public function getAdapter($name);
    public function getAdapters();

    public function setUrl($url);
    public function getUrl();
    public function setTitle($title);
    public function getTitle();

    public function setUserAttributes(array $attributes);
    public function addUserAttributes(array $attributes);
    public function getDefinedUserAttributes(array $attributes, $includeCustom=true);
    public function setUserAttribute($key, $value);
    public function getUserAttribute($key);
    public function getUserAttributes();
    public function removeUserAttributes($key);
    public function clearUserAttributes();

    public function setEvents(array $events);
    public function addEvents(array $events);
    public function addEvent($event, $name=null, $label=null, array $attributes=null);
    public function getEvents();
    public function clearEvents();
}


interface IEvent extends core\IAttributeContainer {
    public function getUniqueId();

    public function setCategory($category);
    public function getCategory();
    public function setName($name);
    public function getName();
    public function setLabel($label);
    public function getLabel();
}

interface IAdapter {
    public function getName();
    public function apply(IHandler $handler, aura\view\IHtmlView $view);

    public function setOptions(array $options);
    public function setOption($key, $val);
    public function getOption($key);
    public function getOptions();
    public function getRequiredOptions();
    public function clearOptions();
    public function validateOptions(core\collection\IInputTree $values, $update=false);

    public function setDefaultUserAttributes(array $attributes);
    public function getDefaultUserAttributes();
}