<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\navigation\menu;

use DecodeLabs\Exceptional;
use df\arch;

use df\core;

interface IMenu extends core\IContextAware, arch\navigation\IEntryListGenerator
{
    public function getId(): core\uri\IUrl;
    public function setSubId($id);
    public function getSubId();
    public function getDisplayName(): string;
    public function getSource();
    public function getSourceId();

    public function initDelegates();
    public function addDelegate(IMenu $menu);
    public function getDelegates();
}

interface ISource
{
    public function getName(): string;
    public function getDisplayName(): string;
    public function loadMenu(core\uri\IUrl $id);
}

interface IListableSource extends ISource
{
    public function loadAllMenus(array $whiteList = null);
}

interface ISourceAdapter
{
    public function loadMenu(ISource $source, core\uri\IUrl $id);
}

trait TResponsiveSourceAdapter
{
    public function loadMenu(ISource $source, core\uri\IUrl $id)
    {
        $func = '_load' . $id->path->getBaseName() . 'Menu';

        if (!method_exists($this, $func)) {
            throw Exceptional::NotFound(
                'Menu ' . $id->path->getBaseName() . ' could not be loaded'
            );
        }

        $output = new arch\navigation\menu\Dynamic($source->getContext(), (string)$id);
        $this->{$func}($output, $source, $id);

        return $output;
    }
}


interface IEntryList extends arch\navigation\IEntryList
{
    public function registerMenu(IMenu $menu);
    public function hasMenu($id);
}

class Cache extends core\cache\Base
{
}
