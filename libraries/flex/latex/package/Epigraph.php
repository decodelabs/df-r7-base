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
    
class Epigraph extends Base {

    protected static $_commands = [
        '@chapapp', '@ehc', '@epicenterfalse', '@epicentertrue', '@epichapapp', '@epipos',
        '@epirhsfalse', '@epirhstrue', '@epirule', '@episource', '@epitemp', '@epitext', 
        '@ept', '@evenfoot', '@evenhead', '@ifundefined', '@oddfoot', '@oodhead',

        'c@page', 'if@epicenter', 'if@epirhs', 'ps@epigraph',

        'afterepigraphskip', 'AtBeginDocument', 'baselineskip', 'beforeepigraphskip', 'clearpage', 
        'cleartoevenpage', 'dropchapter', 'epigraph', 'epigraphflush', 'epigraphhead', 'epigraphrule',
        'epigraphsize', 'epigraphwidth', 'ifodd', 'item', 'itemindent', 'labelsep', 'labelwidth',
        'leftmargin', 'makebox', 'makelabel', 'MessageBreak', 'newif', 'PackageError', 
        'PackageWarningNoLine', 'providecommand', 'ProvidesPackage', 'put', 'qitem', 'qitemlabel',
        'rule', 'small', 'sourceflush', 'textflush', 'textwidth', 'thepage', 'thispagestyle', 'undodrop'
    ];
