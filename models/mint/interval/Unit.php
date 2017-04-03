<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint\interval;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\enum\Base {

    const DAY = 'day';
    const WEEK = 'week';
    const MONTH = 'month';
    const YEAR = 'year';
}