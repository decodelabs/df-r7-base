<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\fileStats;

use df;
use df\core;

class File {
    
    protected static $_textTypes = array(
        'as', 'atom', 'cgi', 'css', 'dtd', 'htaccess', 'htc', 'htm', 'html', 'js', 'json', 'mathml', 
        'php', 'rdf', 'smd', 'sh', 'style', 'svg', 'sql', 'template', 'txt', 'xhtml', 'xht', 'xsd', 
        'xsl', 'xslt', 'xml'
    );
    
    protected $_path;
    protected $_lines = 0;
    protected $_bytes = 0;
    
    public function __construct($path) {
        $this->_path = core\uri\FilePath::factory($path);
        $pathString = (string)$this->_path;
        
        if(is_file($pathString)) {
            if(in_array($this->_path->getExtension(), self::$_textTypes)) {
                $this->_lines = 0;
                
                $fp = fopen($pathString, 'r');
                
                while(fgets($fp)) {
                    $this->_lines++;
                }
                
                fclose($fp);
            }
            
            $this->_bytes = filesize($pathString);
        }
    }
    
    public function getPath() {
        return (string)$this->_path;
    }
    
    public function getPathObject() {
        return $this->_path;
    }
    
    public function getExtension() {
        return $this->_path->getExtension();
    }
    
    public function countLines() {
        return $this->_lines;
    }
    
    public function countBytes() {
        return $this->_bytes;
    }
}