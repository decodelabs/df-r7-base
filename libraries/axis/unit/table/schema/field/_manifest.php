<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;


// Interfaces
interface IField extends opal\schema\IField {
    
}

interface IOneRelationField extends IField, axis\schema\IRelationField {}
interface IManyRelationField extends IField, axis\schema\IRelationField, axis\schema\INullPrimitiveField {}

interface IBridgedRelationField extends IField, axis\schema\IRelationField {
    public function setBridgeUnitId($id);
    public function getBridgeUnitId();
    
    public function getBridgeUnit(core\IApplication $application=null);
    
    public function getLocalPrimaryFieldNames();
    public function getTargetPrimaryFieldNames();
}


interface IOneField extends IOneRelationField, axis\schema\IMultiPrimitiveField {}
interface IOneParentField extends IOneRelationField, axis\schema\IMultiPrimitiveField {}
interface IOneChildField extends IOneRelationField, axis\schema\INullPrimitiveField {}
interface IManyToOneField extends IOneRelationField, axis\schema\IMultiPrimitiveField {}

interface IManyField extends IManyRelationField, IBridgedRelationField, axis\schema\IQueryClauseRewriterField {}

interface IManyToManyField extends IManyRelationField, IBridgedRelationField, axis\schema\IInverseRelationField, axis\schema\IQueryClauseRewriterField {
    public function isDominant($flag=null);
}

interface IOneToManyField extends IManyRelationField, axis\schema\IInverseRelationField, axis\schema\IQueryClauseRewriterField {}
