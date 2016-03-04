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

class Xcolor extends Base {

    const COMMANDS = [
        'adjustUCRGB', 'blendcolors', 'boxframe', 'color', 'colorbox', 'colorlet',
        'colormask', 'colorseriescycle', 'convertcolorspec', 'definecolor', 'definecolors', 'definecolorseries',
        'definecolorset', 'DefineNamedColor', 'extractcolorspec', 'extractcolorspecs', 'fcolorbox',
        'GetGinDriver', 'GinDriver', 'hiderowcolors', 'ifconvertcolorsD', 'ifconvertcolorsU', 'ifdefinecolors',
        'ifglobalcolors', 'ifmaskcolors', 'maskcolors', 'pagecolor', 'preparecolor', 'preparecolorset',
        'providecolor', 'providecolors', 'providecolorset', 'rangeGray', 'rangeHSB', 'rangeHsb', 'rangeRGB',
        'rangetHsb', 'resetcolorseries', 'rowcolors', 'rownum', 'selectcolormodel',
        'showrowcolors', 'substitutecolormodel', 'testcolor', 'textcolor', 'tracingcolors', 'xcolorcmd',
        'xglobal'
    ];
}