<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Category;

use DecodeLabs\R7\Nightfire\CategoryAbstract;

class Description extends CategoryAbstract
{
    public const DEFAULT_BLOCKS = ['SimpleTags', 'RawHtml', 'Markdown'];
    public const DEFAULT_EDITOR_BLOCK = 'SimpleTags';
}
