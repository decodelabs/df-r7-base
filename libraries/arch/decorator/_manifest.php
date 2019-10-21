<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\decorator;

use df;
use df\core;
use df\arch;
use df\aura;

interface IDecorator extends aura\view\ICascadingHelperProvider
{
}


interface IFormDecorator extends IDecorator, arch\node\IForm
{
    public static function factory(arch\node\IFormNode $form): ?IFormDecorator;

    public function renderUi();
}

interface IDelegateDecorator extends IDecorator, arch\node\IForm
{
    public static function factory(arch\node\IDelegate $delegate): ?IDelegateDecorator;

    public function renderUi();
}
