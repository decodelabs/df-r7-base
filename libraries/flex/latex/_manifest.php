<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex;

use df;
use df\core;
use df\flex;
use df\iris;
    
// Exceptions
interface IException {}


// Interfaces
interface IDocument extends iris\map\IEntity {
    // Class
    public function setDocumentClass($class);
    public function getDocumentClass();

    // Options
    public function setOptions(array $options);
    public function addOptions(array $options);
    public function addOption($option);
    public function getOptions();
    public function clearOptions();

    // Packages
    public function addPackage($name, array $options=array());
    public function hasPackage($name);
    public function getPackages();

    // Top matter
    public function setTitle($title);
    public function getTitle();
    public function setAuthor($author);
    public function getAuthor();
    public function setDate($date);
    public function getDate();

}


interface IPackage extends iris\IProcessor {
    
}