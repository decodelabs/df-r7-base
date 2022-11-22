<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\schema;

use DecodeLabs\Exceptional;
use df\core;
use df\mesh;

use df\opal;

trait TField
{
    protected $_name;
    protected $_isNullable = false;
    protected $_defaultValue;
    protected $_comment;
    protected $_hasChanged = false;

    /*
    public function __construct($name, array $args=[]) {
        $this->_setName($name);

        if(method_exists($this, '_init')) {
            $this->_init(...$args);
        }
    }
     */

    public function getFieldType()
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function _setName($name)
    {
        if ($name != $this->_name) {
            $this->_hasChanged = true;
        }

        $this->_name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function setComment($comment)
    {
        if ($comment != $this->_comment) {
            $this->_hasChanged = true;
        }

        $this->_comment = $comment;
        return $this;
    }

    public function getComment()
    {
        return $this->_comment;
    }

    public function isNullable(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_isNullable) {
                $this->_hasChanged = true;
            }

            $this->_isNullable = $flag;
            return $this;
        }

        return $this->_isNullable;
    }

    public function setDefaultValue($value)
    {
        $value = $this->_normalizeDefaultValue($value);

        if ($value != $this->_defaultValue) {
            $this->_hasChanged = true;
        }

        $this->_defaultValue = $value;
        return $this;
    }

    public function getDefaultValue()
    {
        return $this->_defaultValue;
    }

    protected function _normalizeDefaultValue($value)
    {
        return $value;
    }

    public function hasChanged()
    {
        return $this->_hasChanged;
    }

    public function markAsChanged()
    {
        $this->_hasChanged = true;
        return $this;
    }

    public function acceptChanges()
    {
        $this->_hasChanged = false;
        return $this;
    }


    // Ext. serialize
    public function toStorageArray()
    {
        return $this->_getGenericStorageArray();
    }

    protected function _setGenericStorageArray(array $data)
    {
        $this->_name = $data['nam'];

        if (isset($data['nul'])) {
            $this->_isNullable = $data['nul'];
        } else {
            $this->_isNullable = false;
        }

        if (isset($data['def'])) {
            $this->_defaultValue = $data['def'];
        }

        if (isset($data['com'])) {
            $this->_comment = $data['com'];
        }
    }

    protected function _getGenericStorageArray()
    {
        $output = [
            'typ' => $this->getFieldType(),
            'nam' => $this->_name
        ];

        if ($this->_isNullable) {
            $output['nul'] = true;
        }

        if ($this->_defaultValue !== null) {
            $output['def'] = $this->_defaultValue;
        }

        if ($this->_comment !== null) {
            $output['com'] = $this->_comment;
        }

        return $output;
    }
}






trait TField_CharacterSetAware
{
    protected $_characterSet;

    public function setCharacterSet($charset)
    {
        if ($charset != $this->_characterSet) {
            $this->_hasChanged = true;
        }

        $this->_characterSet = $charset;
        return $this;
    }

    public function getCharacterSet()
    {
        return $this->_characterSet;
    }


    // Ext serialize
    protected function _setCharacterSetStorageArray(array $data)
    {
        if (isset($data['chs'])) {
            $this->_characterSet = $data['chs'];
        }
    }

    protected function _getCharacterSetStorageArray()
    {
        $output = [];

        if ($this->_characterSet !== null) {
            $output['chs'] = $this->_characterSet;
        }

        return $output;
    }
}



trait TField_BinaryCollationProvider
{
    protected $_binaryCollation = false;

    public function hasBinaryCollation(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_binaryCollation) {
                $this->_hasChanged = true;
            }

            $this->_binaryCollation = $flag;
            return $this;
        }

        return $this->_binaryCollation;
    }

    // Ext. serialize
    protected function _setBinaryCollationStorageArray(array $data)
    {
        if (isset($data['bic'])) {
            $this->_binaryCollation = $data['bic'];
        } else {
            $this->_binaryCollation = false;
        }
    }

    protected function _getBinaryCollationStorageArray()
    {
        $output = [];

        if ($this->_binaryCollation !== false) {
            $output['bic'] = $this->_binaryCollation;
        }

        return $output;
    }
}


trait TField_LengthRestricted
{
    protected $_length;

    public function setLength($length)
    {
        if ($length === null) {
            $length = $this->_getDefaultLength();
        }

        $this->_hasChanged = true;

        if ($length !== null) {
            $length = (int)$length;
        }

        $this->_length = $length;
        return $this;
    }

    public function getLength()
    {
        return $this->_length;
    }

    protected function _getDefaultLength()
    {
        return null;
    }


    // Ext. serialize
    protected function _setLengthRestrictedStorageArray(array $data)
    {
        if (isset($data['lnt'])) {
            $this->_length = $data['lnt'];
        }
    }

    protected function _getLengthRestrictedStorageArray()
    {
        $output = [];

        if ($this->_length !== null) {
            $output['lnt'] = $this->_length;
        }

        return $output;
    }
}


trait TField_Signed
{
    protected $_isUnsigned = false;

    public function isSigned(bool $flag = null)
    {
        if ($flag !== null) {
            return $this->isUnsigned(!$flag);
        }

        return !$this->_isUnsigned;
    }

    public function isUnsigned(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_isUnsigned) {
                $this->_hasChanged = true;
            }

            $this->_isUnsigned = $flag;
            return $this;
        }

        return $this->_isUnsigned;
    }


    // Ext. serialize
    protected function _setSignedStorageArray(array $data)
    {
        if (isset($data['uns'])) {
            $this->_isUnsigned = $data['uns'];
        } else {
            $this->_isUnsigned = false;
        }
    }

    protected function _getSignedStorageArray()
    {
        $output = [];

        if ($this->_isUnsigned !== false) {
            $output['uns'] = $this->_isUnsigned;
        }

        return $output;
    }
}


trait TField_Zerofill
{
    protected $_zerofill = false;

    public function shouldZerofill(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_zerofill) {
                $this->_hasChanged = true;
            }

            $this->_zerofill = $flag;
            return $this;
        }

        return $this->_zerofill;
    }

    // Ext. serialize
    protected function _setZerofillStorageArray(array $data)
    {
        if (isset($data['zfl'])) {
            $this->_zerofill = $data['zfl'];
        } else {
            $this->_zerofill = false;
        }
    }

    protected function _getZerofillStorageArray()
    {
        $output = [];

        if ($this->_zerofill !== false) {
            $output['zfl'] = $this->_zerofill;
        }

        return $output;
    }
}


trait TField_Numeric
{
    use TField_Signed;
    use TField_Zerofill;



    // Ext. serialize
    protected function _setNumericStorageArray(array $data)
    {
        $this->_setSignedStorageArray($data);
        $this->_setZerofillStorageArray($data);
    }

    protected function _getNumericStorageArray()
    {
        return array_merge(
            $this->_getSignedStorageArray(),
            $this->_getZerofillStorageArray()
        );
    }
}



trait TField_FloatingPointNumeric
{
    use TField_Numeric;

    protected $_precision;
    protected $_scale;

    public function setPrecision($precision)
    {
        if ($precision !== null) {
            $precision = (int)$precision;
        }

        if ($precision < 0) {
            $precision = 0;
        }

        $this->_precision = $precision;
        $this->_hasChanged = true;
        return $this;
    }

    public function getPrecision()
    {
        return $this->_precision;
    }

    public function setScale($scale)
    {
        if ($scale !== null) {
            $scale = (int)$scale;
        }

        $this->_scale = $scale;
        $this->_hasChanged = true;
        return $this;
    }

    public function getScale()
    {
        return $this->_scale;
    }


    // Ext. serialize
    protected function _setFloatingPointNumericStorageArray(array $data)
    {
        $this->_setNumericStorageArray($data);
        $this->_scale = $data['scl'];
        $this->_precision = $data['prs'];
    }

    protected function _getFloatingPointNumericStorageArray()
    {
        return array_merge(
            $this->_getNumericStorageArray(),
            [
                'scl' => $this->_scale,
                'prs' => $this->_precision
            ]
        );
    }
}



trait TField_AutoIncrementable
{
    protected $_autoIncrement = false;

    public function shouldAutoIncrement(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_autoIncrement) {
                $this->_hasChanged = true;
            }

            $this->_autoIncrement = $flag;
            return $this;
        }

        return $this->_autoIncrement;
    }


    // Ext. serialize
    protected function _setAutoIncrementStorageArray(array $data)
    {
        if (isset($data['aui'])) {
            $this->_autoIncrement = $data['aui'];
        } else {
            $this->_autoIncrement = false;
        }
    }

    protected function _getAutoIncrementStorageArray()
    {
        $output = [];

        if ($this->_autoIncrement !== false) {
            $output['aui'] = $this->_autoIncrement;
        }

        return $output;
    }
}



trait TField_AutoTimestamp
{
    protected $_shouldTimestampOnUpdate = false;
    protected $_shouldTimestampAsDefault = true;

    public function shouldTimestampOnUpdate(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_shouldTimestampOnUpdate) {
                $this->_hasChanged = true;
            }

            $this->_shouldTimestampOnUpdate = $flag;
            return $this;
        }

        return $this->_shouldTimestampOnUpdate;
    }

    public function shouldTimestampAsDefault(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_shouldTimestampAsDefault) {
                $this->_hasChanged = true;
            }

            if ($this->_shouldTimestampAsDefault = $flag) {
                $this->setDefaultValue(null);
            }

            return $this;
        }

        return $this->_shouldTimestampAsDefault;
    }

    public function setDefaultValue($value)
    {
        if ($value !== null) {
            $this->_shouldTimestampAsDefault = false;
        }

        return parent::setDefaultValue($value);
    }


    // Ext. serialize
    protected function _setAutoTimestampStorageArray(array $data)
    {
        if (isset($data['toa'])) {
            $this->_shouldTimestampOnUpdate = $data['toa'];
        } else {
            $this->_shouldTimestampOnUpdate = false;
        }

        if (isset($data['tad'])) {
            $this->_shouldTimestampAsDefault = $data['tad'];
        } else {
            $this->_shouldTimestampAsDefault = true;
        }
    }

    protected function _getAutoTimestampStorageArray()
    {
        $output = [];

        if ($this->_shouldTimestampOnUpdate !== false) {
            $output['toa'] = $this->_shouldTimestampOnUpdate;
        }

        if ($this->_shouldTimestampAsDefault !== true) {
            $output['tad'] = $this->_shouldTimestampAsDefault;
        }

        return $output;
    }
}





trait TField_OptionProvider
{
    protected $_options = [];
    protected $_enumType = null;

    public function setOptions(array $options)
    {
        $this->_options = $options;
        $this->_hasChanged = true;
        return $this;
    }

    public function getOptions()
    {
        if ($this->_enumType) {
            return $this->getTypeHandler()->getOptions();
        }

        return $this->_options;
    }

    public function setType($type)
    {
        if ($type !== null) {
            if (is_string($type) && false === strpos($type, '://')) {
                $type = 'type://' . $type;
            }

            $type = mesh\Manager::getInstance()->fetchEntity($type);

            if ($type instanceof core\lang\ITypeRef) {
                $type->checkType('core/lang/IEnum');
                $typeString = 'type://' . $type->getClassPath();
            } elseif ($type instanceof core\lang\IEnumFactory) {
                if ($type instanceof mesh\entity\ILocatorProvider) {
                    $typeString = (string)$type->getEntityLocator();
                } else {
                    $typeString = (new core\lang\TypeRef($type))->getClassPath();
                }
            } else {
                throw Exceptional::InvalidArgument(
                    'Type cannot provide an enum'
                );
            }

            $type = $typeString;
            $this->_options = mesh\Manager::getInstance()
                ->fetchEntity($type)
                    ->getOptions();
        }

        $this->_enumType = $type;
        $this->_hasChanged = true;
        return $this;
    }

    public function getTypeString()
    {
        return $this->_enumType;
    }

    public function getTypeHandler()
    {
        if ($this->_enumType) {
            return mesh\Manager::getInstance()->fetchEntity($this->_enumType);
        }
    }


    // Ext. serialize
    protected function _setOptionStorageArray(array $data)
    {
        if (isset($data['opt'])) {
            $this->_options = (array)$data['opt'];
        }

        if (isset($data['ent'])) {
            $this->_enumType = $data['ent'];
        }
    }

    protected function _getOptionStorageArray()
    {
        $output = [];

        if (!empty($this->_options)) {
            $output['opt'] = $this->_options;
        }

        if ($this->_enumType !== null) {
            $output['ent'] = $this->_enumType;
        }

        return $output;
    }
}




trait TField_BitSizeRestricted
{
    protected $_bitSize;

    public function setBitSize($size)
    {
        $newSize = $this->_normalizeBits($size);

        if ($newSize != $this->_bitSize) {
            $this->_hasChanged = true;
        }

        $this->_bitSize = $newSize;
        return $this;
    }

    public function getBitSize()
    {
        return $this->_bitSize;
    }

    protected function _normalizeBits($size)
    {
        if (is_string($size)) {
            switch (strtolower($size)) {
                case opal\schema\IFieldSize::TINY:
                    $size = 1;
                    break;

                case opal\schema\IFieldSize::SMALL:
                    $size = 8;
                    break;

                case opal\schema\IFieldSize::MEDIUM:
                    $size = 16;
                    break;

                case opal\schema\IFieldSize::LARGE:
                    $size = 32;
                    break;

                case opal\schema\IFieldSize::HUGE:
                    $size = 64;
                    break;

                default:
                    if (is_numeric($size)) {
                        $size = (int)$size;
                    } else {
                        $size = 16;
                    }

                    break;
            }
        }

        $size = (int)$size;

        if ($size > 64) {
            throw Exceptional::InvalidArgument(
                'Maximum bit size is 64'
            );
        } elseif ($size < 1) {
            throw Exceptional::InvalidArgument(
                'Minimum bit size is 1'
            );
        }

        return $size;
    }

    // Ext serialize
    protected function _setBitSizeRestrictedStorageArray(array $data)
    {
        if (isset($data['bit'])) {
            $this->_bitSize = $data['bit'];
        }
    }

    protected function _getBitSizeRestrictedStorageArray()
    {
        $output = [];

        if ($this->_bitSize !== null) {
            $output['bit'] = $this->_bitSize;
        }

        return $output;
    }
}

trait TField_ByteSizeRestricted
{
    protected $_byteSize;

    public function setByteSize($size)
    {
        $newSize = $this->_normalizeBytes($size);

        if ($newSize != $this->_byteSize) {
            $this->_hasChanged = true;
        }

        $this->_byteSize = $newSize;
        return $this;
    }

    public function getByteSize()
    {
        return $this->_byteSize;
    }

    private function _normalizeBytes($size)
    {
        if (is_string($size)) {
            switch (strtolower($size)) {
                case opal\schema\IFieldSize::TINY:
                    $size = 1;
                    break;

                case opal\schema\IFieldSize::SMALL:
                    $size = 2;
                    break;

                case opal\schema\IFieldSize::MEDIUM:
                    $size = 3;
                    break;

                case opal\schema\IFieldSize::LARGE:
                    $size = 4;
                    break;

                case opal\schema\IFieldSize::HUGE:
                    $size = 8;
                    break;

                default:
                    if (is_numeric($size)) {
                        $size = (int)$size;
                    } else {
                        $size = 4;
                    }

                    break;
            }
        }

        $size = (int)$size;

        switch ($size) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 8:
                break;

            default:
                if ($size < 8) {
                    $size = 8;
                } else {
                    throw Exceptional::InvalidArgument(
                        'Maximum byte size is 8'
                    );
                }
        }

        return $size;
    }

    // Ext. serialize
    protected function _setByteSizeRestrictedStorageArray(array $data)
    {
        if (isset($data['byt'])) {
            $this->_byteSize = $data['byt'];
        }
    }

    protected function _getByteSizeRestrictedStorageArray()
    {
        $output = [];

        if ($this->_byteSize !== null) {
            $output['byt'] = $this->_byteSize;
        }

        return $output;
    }
}


trait TField_LargeByteSizeRestricted
{
    protected $_exponentSize;

    public function setExponentSize($size)
    {
        $newSize = $this->_normalizeExponent($size);

        if ($newSize != $this->_exponentSize) {
            $this->_hasChanged = true;
        }

        $this->_exponentSize = $newSize;

        return $this;
    }

    public function getExponentSize()
    {
        return $this->_exponentSize;
    }

    protected function _normalizeExponent($size)
    {
        if (is_string($size)) {
            switch (strtolower($size)) {
                case opal\schema\IFieldSize::TINY:
                case opal\schema\IFieldSize::SMALL:
                    $size = 8;
                    break;

                case opal\schema\IFieldSize::MEDIUM:
                    $size = 16;
                    break;

                case opal\schema\IFieldSize::LARGE:
                    $size = 24;
                    break;

                case opal\schema\IFieldSize::HUGE:
                    $size = 32;
                    break;

                default:
                    if (is_numeric($size)) {
                        $size = (int)$size;
                    } else {
                        $size = 16;
                    }

                    break;
            }
        }

        $size = (int)$size;

        switch ($size) {
            case 8:
            case 16:
            case 24:
            case 32:
                break;

            default:
                if ($size < 8) {
                    $size = 8;
                } elseif ($size < 16) {
                    $size = 16;
                } elseif ($size < 24) {
                    $size = 24;
                } elseif ($size < 32) {
                    $size = 32;
                } else {
                    throw Exceptional::InvalidArgument(
                        'Maximum exponent byte size is 2 ^ 32'
                    );
                }
        }

        return $size;
    }

    // Ext. serialize
    protected function _setLargeByteSizeRestrictedStorageArray(array $data)
    {
        if (isset($data['lby'])) {
            $this->_exponentSize = $data['lby'];
        }
    }

    protected function _getLargeByteSizeRestrictedStorageArray()
    {
        $output = [];

        if ($this->_exponentSize !== null) {
            $output['lby'] = $this->_exponentSize;
        }

        return $output;
    }
}



trait TAutoGeneratorField
{
    protected $_autoGenerate = true;

    public function shouldAutoGenerate(bool $flag = null)
    {
        if ($flag !== null) {
            $flag = $flag;

            if ($flag != $this->_autoGenerate) {
                $this->_hasChanged = true;
            }

            $this->_autoGenerate = $flag;
            return $this;
        }

        return $this->_autoGenerate;
    }
}



trait TField_TargetPrimaryFieldAwareRelation
{
    protected $_targetRelationManifest;

    public function getTargetRelationManifest()
    {
        if ($this->_targetRelationManifest) {
            return $this->_targetRelationManifest;
        } elseif ($this->_targetRelationManifest === false) {
            return null;
        }

        $primaryIndex = $this->getTargetPrimaryIndex();

        if ($primaryIndex) {
            $this->_targetRelationManifest = new opal\schema\RelationManifest($primaryIndex);
        } else {
            $this->_targetRelationManifest = false;
            return null;
        }

        return $this->_targetRelationManifest;
    }
}


trait TField_BridgedRelation
{
    public function isSelfReference()
    {
        $local = $this->getBridgeLocalFieldName();
        $target = $this->getBridgeTargetFieldName();

        return $local == $target . IBridgedRelationField::SELF_REFERENCE_SUFFIX
            || $target == $local . IBridgedRelationField::SELF_REFERENCE_SUFFIX;
    }
}
