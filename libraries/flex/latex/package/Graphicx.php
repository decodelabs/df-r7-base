<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\package;

use df;
use df\core;
use df\flex;
use df\iris;

class Graphicx extends Base {

    const COMMANDS = [
        'DeclareGraphicsExtensions', 'DeclareGraphicsRule', 'graphicspath', 'includegraphics',
        'reflectbox', 'resizebox', 'rotatebox', 'scalebox',
    ];
}