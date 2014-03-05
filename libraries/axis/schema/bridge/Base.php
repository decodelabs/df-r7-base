<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\bridge;

use df;
use df\core;
use df\axis;
use df\opal;

abstract class Base implements axis\schema\IBridge {
    
    protected $_unit;
    protected $_axisSchema;
    protected $_targetSchema;
    
    public function __construct(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $axisSchema, opal\schema\ISchema $targetSchema=null) {
        $this->_unit = $unit;
        $this->_axisSchema = $axisSchema;
        
        if(!$targetSchema) {
            $targetSchema = $this->_createTargetSchema();
        }
        
        $this->_targetSchema = $targetSchema;
    }
    
    abstract protected function _createTargetSchema();
    
    public function getUnit() {
        return $this->_unit;
    }
    
    public function getAxisSchema() {
        return $this->_axisSchema;
    }
    
    public function getTargetSchema() {
        return $this->_targetSchema;
    }
    
    
    public function updateTargetSchema() {
        $axisPrimaryIndex = $this->_axisSchema->getPrimaryIndex();
        $lastAxisPrimaryIndex = $this->_axisSchema->getLastPrimaryIndex();
        $targetPrimaryIndex = $this->_targetSchema->getPrimaryIndex();
        
        $supportsIndexes = $this->_targetSchema instanceof opal\schema\IIndexProvider;
        
        
        if(!$primaryIndexHasChanged = $this->_axisSchema->hasPrimaryIndexChanged()) {
            $lastAxisPrimaryIndex = $axisPrimaryIndex;
        }
        
        
        // Remove indexes
        if($supportsIndexes) {
            foreach($this->_axisSchema->getIndexesToRemove() as $name => $axisIndex) {
                if($axisIndex->isSingleMultiPrimitiveField() && !$axisIndex->isUnique()) {
                    $axisField = $fieldReferences[0]->getField();
                    $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);

                    if($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                        foreach($primitive->getPrimitives() as $name => $child) {
                            $this->_targetSchema->removeIndex(
                                $this->_getIndexName($axisIndex, false, $child)
                            );
                        }

                        continue;
                    }
                }

                $this->_targetSchema->removeIndex(
                    $this->_getIndexName($axisIndex, $axisIndex === $lastAxisPrimaryIndex)
                );
            }
        }
        
        
        // Remove fields
        foreach($this->_axisSchema->getFieldsToRemove() as $name => $axisField) {
            if($axisField instanceof opal\schema\IMultiPrimitiveField) {
                foreach($axisField->getPrimitiveFieldNames() as $name) {
                    $this->_targetSchema->removeField($name);
                }
            } else if($axisField instanceof opal\schema\INullPrimitiveField) {
                continue;
            } else {
                $this->_targetSchema->removeField($name);
            }
        }
        
        // Update fields
        foreach($this->_axisSchema->getFieldsToUpdate() as $name => $axisField) {
            $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);
            
            if($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                $childPrimitives = $primitive->getPrimitives();
                $lastChild = $firstChild = array_shift($childPrimitives);
                
                $this->_targetSchema->replacePreparedField(
                    $this->_createField($firstChild)
                );
                
                foreach($childPrimitives as $child) {
                    $this->_targetSchema->addPreparedFieldAfter(
                        $lastChild->getName(), $this->_createField($child)
                    );
                    
                    $lastChild = $child;
                }
            } else if($axisField instanceof opal\schema\INullPrimitiveField) {
                continue;
            } else {
                $this->_targetSchema->replacePreparedField(
                    $this->_createField($primitive)
                );
            }
        }
        
        // Add fields
        foreach($this->_axisSchema->getFieldsToAdd() as $name => $axisField) {
            $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);
            
            if($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                foreach($primitive->getPrimitives() as $name => $child) {
                    $this->_targetSchema->addPreparedField(
                        $this->_createField($child)
                    );
                }
            } else if($axisField instanceof opal\schema\INullPrimitiveField) {
                continue;
            } else {
                $this->_targetSchema->addPreparedField(
                    $this->_createField($primitive)
                );
            }
        }
        
        
        if($supportsIndexes) {
            // Update indexes
            foreach($this->_axisSchema->getIndexesToUpdate() as $name => $axisIndex) {
                if($primaryIndexHasChanged 
                && $axisIndex === $lastAxisPrimaryIndex
                && $targetPrimaryIndex) {
                    $newName = $this->_getIndexName($axisIndex, false);
                    $oldName = $targetPrimaryIndex->getName();
                    
                    if($newName != $oldName) {
                        $this->_targetSchema->renameIndex($oldName, $newName);
                    }
                }
                
                $isPrimary = $axisIndex === $axisPrimaryIndex;
                
                foreach($this->_createIndexes($axisIndex, $isPrimary) as $newIndex) {
                    $this->_targetSchema->replacePreparedIndex($newIndex);
                }
                
                if($isPrimary) {
                    $this->_targetSchema->setPrimaryIndex($targetIndex);
                }
            }
        
        
            // Add indexes
            foreach($this->_axisSchema->getIndexes() as $name => $axisIndex) {
                $isPrimary = $axisIndex === $axisPrimaryIndex;
                $targetIndex = null;
                
                foreach($this->_createIndexes($axisIndex, $isPrimary) as $newIndex) {
                    $this->_targetSchema->addPreparedIndex($newIndex);

                    if(!$targetIndex) {
                        $targetIndex = $newIndex;
                    }
                }
                
                if($isPrimary) {
                    if($targetPrimaryIndex) {
                        $newName = $this->_getIndexName($axisIndex, false);
                        $oldName = $targetPrimaryIndex->getName();
                        
                        if($newName != $oldName) {
                            $this->_targetSchema->renameIndex($oldName, $newName);
                        }
                    }
                    
                    $this->_targetSchema->setPrimaryIndex($targetIndex);
                }
            }
        }

        return $this->_targetSchema;
    }

// Primitives
    protected function _createField(opal\schema\IPrimitive $primitive) {
        $type = $primitive->getType();
        $func = '_create'.$type.'Field';
        
        if(!method_exists($this, $func)) {
            throw new axis\schema\RuntimeException(
                'Primitive '.$type.' is currently not supported by RDBMS based tables, for field '.$primitive->getName()
            );
        }
        
        return $this->{$func}($primitive);
    }
    
    abstract protected function _createBinaryField(opal\schema\IPrimitive $primitive);
    abstract protected function _createBitField(opal\schema\IPrimitive $primitive);
    abstract protected function _createBlobField(opal\schema\IPrimitive $primitive);
    abstract protected function _createBooleanField(opal\schema\IPrimitive $primitive);
    abstract protected function _createCharField(opal\schema\IPrimitive $primitive);
    abstract protected function _createCurrencyField(opal\schema\IPrimitive $primitive);
    abstract protected function _createDataObjectField(opal\schema\IPrimitive $primitive);
    abstract protected function _createDateField(opal\schema\IPrimitive $primitive);
    abstract protected function _createDateTimeField(opal\schema\IPrimitive $primitive);
    abstract protected function _createDecimalField(opal\schema\IPrimitive $primitive);
    abstract protected function _createEnumField(opal\schema\IPrimitive $primitive);
    abstract protected function _createFloatField(opal\schema\IPrimitive $primitive);
    abstract protected function _createGuidField(opal\schema\IPrimitive $primitive);
    abstract protected function _createIntegerField(opal\schema\IPrimitive $primitive);
    abstract protected function _createSetField(opal\schema\IPrimitive $primitive);
    abstract protected function _createTextField(opal\schema\IPrimitive $primitive);
    abstract protected function _createTimeField(opal\schema\IPrimitive $primitive);
    abstract protected function _createTimestampField(opal\schema\IPrimitive $primitive);
    abstract protected function _createVarbinaryField(opal\schema\IPrimitive $primitive);
    abstract protected function _createVarcharField(opal\schema\IPrimitive $primitive);
    abstract protected function _createYearField(opal\schema\IPrimitive $primitive);
    
    
    
// Indexes
    protected function _createIndexes(opal\schema\IIndex $axisIndex, $isPrimary) {
        $output = [];
        $fieldReferences = $axisIndex->getFieldReferences();

        if($axisIndex->isSingleMultiPrimitiveField() && !$axisIndex->isUnique()) {
            $axisField = $fieldReferences[0]->getField();
            $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);

            if($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                foreach($primitive->getPrimitives() as $name => $child) {
                    $output[] = $this->_targetSchema->createIndex($this->_getIndexName($axisIndex, $isPrimary, $child), [])
                        ->addField(
                            $this->_targetSchema->getField($child->getName()), 
                            $fieldReferences[0]->getSize(), 
                            $fieldReferences[0]->isDescending()
                        );
                }

                return $output;
            }
        }


        $targetIndex = $this->_targetSchema->createIndex($this->_getIndexName($axisIndex, $isPrimary), [])
            ->isUnique($axisIndex->isUnique());
        
        foreach($fieldReferences as $ref) {
            $axisField = $ref->getField();
            $primitive = $axisField->toPrimitive($this->_unit, $this->_axisSchema);
            
            if($primitive instanceof opal\schema\IMultiFieldPrimitive) {
                foreach($primitive->getPrimitives() as $name => $child) {
                    $targetIndex->addField(
                        $this->_targetSchema->getField($child->getName()), 
                        $ref->getSize(), 
                        $ref->isDescending()
                    );
                }
            } else if($axisField instanceof opal\schema\INullPrimitiveField) {
                throw new axis\LogicException(
                    'You cannot put indexes on NullPrimitive fields'
                );
            } else {
                $targetIndex->addField(
                    $this->_targetSchema->getField($primitive->getName()), 
                    $ref->getSize(), 
                    $ref->isDescending()
                );
            }
        }
        
        return [$targetIndex];
    }
    
    protected function _getIndexName(opal\schema\IIndex $axisIndex, $isPrimary, opal\schema\IPrimitive $primitive=null) {
        if($primitive) {
            return 'idx_'.$primitive->getName();
        } else {
            return 'idx_'.$axisIndex->getName();
        }
    }
}
