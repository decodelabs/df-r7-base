<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf;

use df;
use df\core;
use df\neon;

class Document implements IDocument {
    
    use TEntityCollection;
    use core\TStringProvider;


    protected $_comments = [];
    protected $_headers = [];
    protected $_classes = [];
    protected $_tables = [];

    /*
    protected $_blocks = [];
    protected $_layers = [];
    protected $_styles = [];
    protected $_views = [];
    */

    public function __construct() {

    }


// Comments
    public function addComment($comment) {
        $this->_comments[] = (string)$comment;
        return $this;
    }

    public function getComments() {
        return $this->_comments;
    }

    public function clearComments() {
        $this->_comments = [];
        return $this;
    }


// Headers
    public function setHeaders(array $headers) {
        return $this->clearHeaders()->addHeaders($headers);
    }

    public function addHeaders(array $headers) {
        foreach($headers as $key => $value) {
            $this->setHeader($key, $value);
        }

        return $this;
    }

    public function setHeader($key, $value) {
        $key = strtoupper(ltrim($key, '$'));

        if(!isset(self::$_headerTypes[$key])) {
            throw new InvalidArgumentException(
                'Header not recognised: '.$key
            );
        }

        if($value === null) {
            unset($this->_headers[$key]);
            return $this;
        }

        $type = self::$_headerTypes[$key];

        if(in_array($key, ['TDCREATE', 'TDUCREATE', 'TDUPDATE', 'TDUUPDATE'])) {
            $value = core\time\Date::factory($value);
        } else if(in_array($key, ['TDINDWG', 'TDUSRTIMER'])) {
            $value = core\time\Duration::factory($value);
        } else if(is_array($type)) {
            $value = core\math\Vector::factory($value, count($type));
        } else {
            if((0 <= $type && $type <= 9)
            || (300 <= $type && $type <= 309)
            || (1000 <= $type && $type <= 1009)) {
                $value = (string)$value;
            } else 
            if((60 <= $type && $type <= 79)
            || (90 <= $type && $type <= 99)
            || (170 <= $type && $type <= 175)
            || (280 <= $type && $type <= 289)
            || (370 <= $type && $type <= 379)
            || (380 <= $type && $type <= 389)
            || (400 <= $type && $type <= 409)
            || (1060 <= $type && $type <= 1071)) {
                $value = (int)$value;
            } else 
            if((40 <= $type && $type <= 58)
            || (140 <= $type && $type <= 147)
            || (1010 <= $type && $type <= 1059)) {
                $value = (float)$value;
            } else 
            if($type == 100 || $type == 102) {
                $value = substr($value, 0, 255);
            } else 
            if($type == 105
            || (310 <= $type && $type <= 319)
            || (320 <= $type && $type <= 329)
            || (330 <= $type && $type <= 369)) {
                if(is_numeric($value)) {
                    $value = dechex($value);
                } else {
                    $value = (string)$value;
                }
            }
        }

        $this->_headers[$key] = $value;
        return $this;
    }

    public function getHeader($key) {
        $key = strtoupper(ltrim($key, '$'));

        if(isset($this->_headers[$key])) {
            return $this->_headers[$key];
        }
    }

    public function hasHeader($key) {
        $key = strtoupper(ltrim($key, '$'));
        return isset($this->_headers[$key]);
    }

    public function removeHeader($key) {
        $key = strtoupper(ltrim($key, '$'));
        unset($this->_headers[$key]);
        return $this;
    }

    public function getHeaders() {
        return $this->_headers;
    }

    public function clearHeaders() {
        $this->_headers = [];
        return $this;
    }



// Classes
    public function setClasses(array $classes) {
        return $this->clearClasses()->addClasses($classes);
    }

    public function addClasses(array $classes) {
        foreach($classes as $class) {
            if($class instanceof IAppClass) {
                $this->addClass($class);
            }
        }

        return $this;
    }

    public function newClass($dxfName, $className, $appName) {
        return new AppClass($dxfName, $className, $appName);
    }

    public function addClass(IAppClass $class) {
        $this->_classes[$class->getDxfName()] = $class;
        return $this;
    }

    public function hasClass($dxfName) {
        if($dxfName instanceof IAppClass) {
            $dxfName = $dxfName->getDxfName();
        }

        $dxfName = strtoupper($dxfName);
        return isset($this->_classes[$dxfName]);
    }

    public function removeClass($dxfName) {
        if($dxfName instanceof IAppClass) {
            $dxfName = $dxfName->getDxfName();
        }

        $dxfName = strtoupper($dxfName);
        unset($this->_classes[$dxfName]);
        return $this;
    }

    public function getClass($dxfName) {
        if($dxfName instanceof IAppClass) {
            $dxfName = $dxfName->getDxfName();
        }

        $dxfName = strtoupper($dxfName);

        if(isset($this->_classes[$dxfName])) {
            return $This->_classes[$dxfName];
        }
    }

    public function getClasses() {
        return $this->_classes;
    }

    public function clearClasses() {
        $this->_classes = [];
        return $this;
    }



// Tables
    public function setTables(array $tables) {
        return $this->clearTables()->addTables($tables);
    }

    public function addTables(array $tables) {
        foreach($tables as $table) {
            if($table instanceof ITable) {
                $this->addTable($table);
            }
        } 

        return $this;
    }

    public function addTable(ITable $table) {
        $this->_tables[$table->getType().':'.$table->getName()] = $table;
        return $this;
    }

    public function getTables() {
        return $this->_tables;
    }

    public function clearTables() {
        $this->_tables = [];
        return $this;
    }



    public function newAppIdTable($name) {
        $this->addTable($output = new neon\vector\dxf\table\AppId($name));
        return $output;
    }

    public function newBlockRecordTable($name) {
        $this->addTable($output = new neon\vector\dxf\table\BlockRecord($name));
        return $output;
    }

    public function newLayer($name) {
        $this->addTable($output = new neon\vector\dxf\table\Layer($name));
        return $output;
    }

    public function newLineType($name) {
        $this->addTable($output = new neon\vector\dxf\table\LineType($name));
        return $output;
    }

    public function newStyle($name) {
        $this->addTable($output = new neon\vector\dxf\table\Style($name));
        return $output;
    }

    public function newView($name) {
        $this->addTable($output = new neon\vector\dxf\table\View($name));
        return $output;
    }

    public function newViewportTable($name) {
        $this->addTable($output = new neon\vector\dxf\table\Viewport($name));
        return $output;
    }


// String
    public function toString() {
        $output = '';

        // Comments
        foreach($this->_comments as $comment) {
            $output .= $this->_writeComment($comment);
        }


        // Headers
        if(!$this->hasHeader('acadver')) {
            $this->setHeader('acadver', 'AC1009');
        }

        if(!$this->hasHeader('insbase')) {
            $this->setHeader('insbase', [0, 0, 0]);
        }

        if(!$this->hasHeader('extmin')) {
            $this->setHeader('extmin', [0, 0]);
        }

        if(!$this->hasHeader('extmax')) {
            $this->setHeader('extmax', [0, 0]);
        }

        $headers = [];

        foreach($this->_headers as $key => $value) {
            $headers[] = $this->_writeHeader($key, $value);
        }

        $output .= $this->_writeSection('header', $headers);


        // Classes
        $output .= $this->_writeSection('classes', $this->_classes);

        // Tables
        $tableGroups = ['VPORT' => [], 'LTYPE' => [], 'LAYER' => [], 'STYLE' => [], 'VIEW' => []];
        $tables = [];

        foreach($this->_tables as $table) {
            $tableGroups[$table->getType()][] = (string)$table;
        }

        foreach($tableGroups as $type => $set) {
            $tables[] = sprintf(" 0\nTABLE\n 2\n%s\n 70\n%d\n%s 0\nENDTAB\n", $type, count($set), implode($set));
        }

        $output .= $this->_writeSection('tables', $tables);

        // Blocks
        $output .= $this->_writeSection('blocks', []);

        // Entities
        $output .= $this->_writeSection('entities', $this->_entities);

        $output .= " 0\nEOF\n";

        return $output;
    }

    public static function _writeComment($comment) {
        if(!strlen($comment)) {
            return null;
        }

        return sprintf(" 999\n%s\n", $comment);
    }

    public static function _writeName($name) {
        return sprintf(" 9\n\$%s\n", strtoupper($name));
    }

    public static function _writePoint(core\math\IVector $vector=null, $index=0, $default=null) {
        if(!$vector) {
            if($default === null) {
                return null;
            }

            $vector = core\math\Vector::factory($default);
        }

        $output = [];

        foreach($vector as $i => $value) {
            $output[] = sprintf(" %s\n%F\n", 10 * ($i + 1) + $index, $value);
        }

        return implode($output);
    }

    public static function _writeSection($name, array $data) {
        return sprintf(" 0\nSECTION\n 2\n%s\n%s 0\nENDSEC\n", strtoupper($name), implode($data));
    }

    public static function _writeHeader($key, $value) {
        if($value instanceof core\math\IVector) {
            $value = self::_writePoint($value);
        } else if($value instanceof core\time\IDate) {
            $value = ($value->format('U') / 86400) + 2440587.5;
        } else if($value instanceof core\time\IDuration) {
            $value = $value->getDays();
        } else {
            $value = sprintf(" %s\n%s\n", self::$_headerTypes[$key], $value);
        }

        return self::_writeName($key).$value;
    }


    


// Data
    protected static $_headerTypes = [
        'ACADMAINTVER' => 70,
        'ACADVER' => 1,
        'ANGBASE' => 50,
        'ANGDIR' => 70,
        'ATTMODE' => 70,
        'AUNITS' => 70,
        'AUPREC' => 70,
        'CECOLOR' => 62,
        'CELTSCALE' => 40,
        'CELTYPE' => 6,
        'CELWEIGHT' => 370,
        'CPSNID' => 390,
        'CEPSNTYPE' => 380,
        'CHAMFERA' => 40,
        'CHAMFERB' => 40,
        'CHAMFERC' => 40,
        'CHAMFERD' => 40,
        'CLAYER' => 8,
        'CMLJUST' => 70,
        'CMLSCALE' => 40,
        'CMLSTYLE' => 2,
        'DIMADEC' => 70,
        'DIMALT' => 70,
        'DIMALTD' => 70,
        'DIMALTF' => 40,
        'DIMALTRND' => 40,
        'DIMALTTD' => 70,
        'DIMALTTZ' => 70,
        'DIMALTU' => 70,
        'DIMALTZ' => 70,
        'DIMAPOST' => 1,
        'DIMASO' => 70,
        'DIMASZ' => 40,
        'DIMATFIT' => 70,
        'DIMAUNIT' => 70,
        'DIMAZIN' => 70,
        'DIMBLK' => 1,
        'DIMBLK1' => 1,
        'DIMBLK2' => 1,
        'DIMCEN' => 40,
        'DIMCLRD' => 70,
        'DIMCLRE' => 70,
        'DIMCLRT' => 70,
        'DIMDEC' => 70,
        'DIMDLE' => 40,
        'DIMDLI' => 40,
        'DIMDSEP' => 70,
        'DIMEXE' => 40,
        'DIMEXO' => 40,
        'DIMFAC' => 40,
        'DIMGAP' => 40,
        'DIMJUST' => 70,
        'DIMLDRBLK' => 1,
        'DIMLFAC' => 40,
        'DIMLIM' => 70,
        'DIMLUNIT' => 70,
        'DIMLWD' => 70,
        'DIMLWE' => 70,
        'DIMPOST' => 1,
        'DIMRND' => 40,
        'DIMSAH' => 70,
        'DIMSCALE' => 40,
        'DIMSD1' => 70,
        'DIMSD2' => 70,
        'DIMSE1' => 70,
        'DIMSE2' => 70,
        'DIMSHO' => 70,
        'DIMSOXD' => 70,
        'DIMSTYLE' => 2,
        'DIMTAD' => 70,
        'DIMTDEC' => 70,
        'DIMTFAC' => 40,
        'DIMTIH' => 70,
        'DIMTIX' => 70,
        'DIMTM' => 40,
        'DIMTMOVE' => 70,
        'DIMTOFL' => 70,
        'DIMTOH' => 70,
        'DIMTOL' => 70,
        'DIMTOLJ' => 70,
        'DIMTP' => 40,
        'DIMTSZ' => 40,
        'DIMTVP' => 40,
        'DIMTXSTY' => 7,
        'DIMTXT' => 40,
        'DIMTZIN' => 70,
        'DIMUPT' => 70,
        'DIMZIN' => 70,
        'DISPSILH' => 70,
        'DWGCODEPAGE' => 3,
        'ELEVATION' => 40,
        'ENDCAPS' => 280,
        'EXTMAX' => [10, 20, 30],
        'EXTMIN' => [10, 20, 30],
        'EXTNAMES' => 290,
        'FILLETRAD' => 40,
        'FILLMODE' => 70,
        'FINGERPRINTGUID' => 2,
        'HANDSEED' => 5,
        'HYPERLINKBASE' => 1,
        'INSBASE' => [10, 20, 30],
        'INSUNITS' => 70,
        'JOINSTYLE' => 280,
        'LIMCHECK' => 70,
        'LIMMAX' => [10, 20],
        'LIMMIN' => [10, 20],
        'LTSCALE' => 40,
        'LUNITS' => 70,
        'LUPREC' => 70,
        'LWDISPLAY' => 290,
        'MAXACTVP' => 70,
        'MEASUREMENT' => 70,
        'MENU' => 1,
        'MIRRTEXT' => 70,
        'ORTHOMODE' => 70,
        'PDMODE' => 70,
        'PDSIZE' => 40,
        'PELEVATION' => 40,
        'PEXTMAX' => [10, 20, 30],
        'PEXTMIN' => [10, 20, 30],
        'PINSBASE' => [10, 20, 30],
        'PLIMCHECK' => 70,
        'PLIMMAX' => [10, 20],
        'PLIMMIN' => [10, 20],
        'PLINEGEN' => 70,
        'PLINEWID' => 40,
        'PROXYGRAPHICS' => 70,
        'PSLTSCALE' => 70,
        'PSTYLEMODE' => 290,
        'PSVPSCALE' => 40,
        'PUCSBASE' => 2,
        'PUCSNAME' => 2,
        'PUCSORG' => [10, 20, 30],
        'PUCSORGBACK' => [10, 20, 30],
        'PUCSORGBOTTOM' => [10, 20, 30],
        'PUCSORGFRONT' => [10, 20, 30],
        'PUCSORGLEFT' => [10, 20, 30],
        'PUCSORGRIGHT' => [10, 20, 30],
        'PUCSORGTOP' => [10, 20, 30],
        'PUCSORTHOREF' => 2,
        'PUCSORTHOVIEW' => 70,
        'PUCSXDIR' => [10, 20, 30],
        'PUCSYDIR' => [10, 20, 30],
        'QTEXTMODE' => 70,
        'REGENMODE' => 70,
        'SHADEDGE' => 70,
        'SHADEDIF' => 70,
        'SKETCHINC' => 40,
        'SKPOLY' => 70,
        'SPLFRAME' => 70,
        'SPLINESEGS' => 70,
        'SPLINETYPE' => 70,
        'SURFTAB1' => 70,
        'SURFTAB2' => 70,
        'SURFTYPE' => 70,
        'SURFU' => 70,
        'SURFV' => 70,
        'TDCREATE' => 40,
        'TDINDWG' => 40,
        'TDUCREATE' => 40,
        'TDUPDATE' => 40,
        'TDUSRTIMER' => 40,
        'TDUUPDATE' => 40,
        'TEXTSIZE' => 40,
        'TEXTSTYLE' => 7,
        'THICKNESS' => 40,
        'TILEMODE' => 70,
        'TRACEWID' => 40,
        'TREEDEPTH' => 70,
        'UCSBASE' => 2,
        'UCSNAME' => 2,
        'UCSORG' => [10, 20, 30],
        'UCSORGBACK' => [10, 20, 30],
        'UCSORGBOTTOM' => [10, 20, 30],
        'UCSORGFRONT' => [10, 20, 30],
        'UCSORGLEFT' => [10, 20, 30],
        'UCSORGRIGHT' => [10, 20, 30],
        'UCSORGTOP' => [10, 20, 30],
        'UCSORTHOREF' => 2,
        'UCSORTHOVIEW' => 70,
        'UCSXDIR' => [10, 20, 30],
        'UCSYDIR' => [10, 20, 30],
        'UNITMODE' => 70,
        'USERI1' => 70,
        'USERI2' => 70,
        'USERI3' => 70,
        'USERI4' => 70,
        'USERI5' => 70,
        'USERR1' => 40,
        'USERR2' => 40,
        'USERR3' => 40,
        'USERR4' => 40,
        'USERR5' => 40,
        'USRTIMER' => 70,
        'VERSIONGUID' => 2,
        'VISRETAIN' => 70,
        'WORLDVIEW' => 70,
        'XEDIT' => 290,

        'FASTZOOM' => 70,
        'GRIDMODE' => 70,
        'GRIDUNIT' => [10, 20],
        'SNAPANG' => 50,
        'SNAPBASE' => 10, 20,
        'SNAPISOPAIR' => 70,
        'SNAPMODE' => 70,
        'SNAPSTYLE' => 70,
        'SNAPUNIT' => [10, 20],
        'VIEWCTR' => [10, 20],
        'VIEWDIR' => [10, 20, 30],
        'VIEWSIZE' => 40
    ];
}