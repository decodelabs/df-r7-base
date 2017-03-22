<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\health\myFitnessPal;

use df;
use df\core;
use df\spur;
use df\link;

class Mediator implements IMediator {

    use spur\THttpMediator;

    const BASE_URL = 'https://www.myfitnesspal.com/';
}