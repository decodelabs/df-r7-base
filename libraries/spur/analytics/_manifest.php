<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\analytics;

use DecodeLabs\R7\Mint\Currency as MintCurrency;
use df\aura;
use df\core;

interface IHandler
{
    public function apply(aura\view\IHtmlView $view);

    public function addConfigAdapters();
    public function addAdapter(IAdapter $adapter);
    public function getAdapter($name);
    public function getAdapters();

    public function setUrl($url);
    public function getUrl();
    public function setTitle(?string $title);
    public function getTitle(): ?string;

    public function setUserAttributes(array $attributes);
    public function addUserAttributes(array $attributes);
    public function getDefinedUserAttributes(array $attributes, $includeCustom = true);
    public function setUserAttribute($key, $value);
    public function getUserAttribute($key);
    public function getUserAttributes();
    public function removeUserAttributes($key);
    public function clearUserAttributes();

    public function setEvents(array $events);
    public function addEvents(array $events);
    public function addEvent($event, $name = null, $label = null, array $attributes = null);
    public function getEvents();
    public function clearEvents();

    public function setECommerceTransactions(array $transactions);
    public function addECommerceTransactions(array $transactions);
    public function addECommerceTransaction($id, MintCurrency $amount = null, $affiliation = null, MintCurrency $shipping = null, MintCurrency $tax = null);
    public function getECommerceTransactions();
    public function clearECommerceTransactions();
}


interface IEvent extends core\collection\IAttributeContainer
{
    public function getUniqueId();

    public function setCategory($category);
    public function getCategory();
    public function setName($name);
    public function getName(): string;
    public function setLabel($label);
    public function getLabel();
}

interface IECommerceTransaction extends core\collection\IAttributeContainer
{
    public function setId(string $id);
    public function getId(): string;
    public function setAffiliation($affiliation);
    public function getAffiliation();
    public function setAmount(MintCurrency $amount);
    public function getAmount();
    public function setShippingAmount(MintCurrency $shipping = null);
    public function getShippingAmount();
    public function setTaxAmount(MintCurrency $tax = null);
    public function getTaxAmount();
}

interface IAdapter
{
    public function getName(): string;
    public function apply(IHandler $handler, aura\view\IHtmlView $view);

    public function setOptions(array $options);
    public function setOption($key, $val);
    public function getOption($key, $default = null);
    public function getOptions();
    public function getRequiredOptions();
    public function clearOptions();
    public function validateOptions(core\collection\IInputTree $values, $update = false);

    public function setDefaultUserAttributes(array $attributes);
    public function getDefaultUserAttributes();
    public function getDefaultUserAttributeMap();
}

interface ILegacyAdapter extends IAdapter
{
}
