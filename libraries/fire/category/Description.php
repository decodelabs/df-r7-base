<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\category;

use df;
use df\core;
use df\fire;

class Description extends Base {

    const REQUIRED_OUTPUT_TYPES = ['Html'];
    const DEFAULT_BLOCKS = ['SimpleTags', 'RawHtml', 'Markdown'];
    const DEFAULT_EDITOR_BLOCK = 'SimpleTags';
}