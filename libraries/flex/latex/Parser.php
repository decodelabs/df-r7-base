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
    
class Parser extends iris\Parser {

    public $document;

    public function __construct(Lexer $lexer) {
        parent::__construct($lexer, [

        ]);
    }

    public function parseRoot() {
        $this->document = new flex\latex\map\Document($this->token->getLocation());
        $this->unit->addEntity($this->document);

        $this->_extractDocumentClass();
        $this->_extractHeadData();
        $this->_extractDocument();

        core\dump($this);
    }

    protected function _extractDocumentClass() {
        $this->extractMatch('command', null, 'documentclass');
        
        // Options
        if($this->extractIfValue('[')) {
            while(true) {
                $word = $this->extractMatch('word');
                $this->document->addOption($word->value);

                if($this->extractIfValue(']')) {
                    break;
                } else {
                    $this->extractValue(',');
                }
            }
        }
        
        // Class
        $this->extractValue('{');
        $this->document->setDocumentClass($this->extract('word')->value);
        $this->extractValue('}');
    }

    protected function _extractHeadData() {
        while(true) {
            $command = $this->extractMatch('command');

            switch($command->value) {
                case 'usepackage':
                    $this->_extractPackageImport();
                    continue 2;

                case 'makeatletter':
                    $this->_skipHacks();
                    continue 2;

                case 'title':
                    $this->_extractTitle();
                    continue 2;

                case 'author':
                    $this->_extractAuthor();
                    continue 2;

                case 'date':
                    $this->_extractDocDate();
                    continue 2;

            }

            if($command->isValue('begin') && $this->peek(2)->is('word=document')) {
                break;
            }

            throw new iris\UnexpectedTokenException(
                'Unexpected header token',
                $command
            );
        }
    }

    protected function _extractPackageImport() {
        // Options
        $options = array();

        if($this->extractIfValue('[')) {
            while(true) {
                $word = $this->extractMatch('word');
                $option = $word->value;
                $value = true;

                if($this->extractIfValue('=')) {
                    $value = $this->extractMatch('word')->value;
                } 

                $options[$option] = $value;

                if($this->extractIfValue(']')) {
                    break;
                } else {
                    $this->extractValue(',');
                }
            }
        }

        // Names
        $this->extractValue('{');

        while(true) {
            $word = $this->extractMatch('word');
            $this->document->addPackage($word->value, $options);

            if($this->extractIfValue('}')) {
                break;
            } else {
                $this->extractValue(',');
            }
        }
    }

    protected function _skipHacks() {
        while(!$this->token->is('command=makeatother')) {
            $this->extract();
        }

        $this->extractMatch('command', null, 'makeatother');
    }

    protected function _extractTitle() {
        $this->extractValue('{');
        $title = '';

        while($token = $this->extractIfMatch('word')) {
            $title .= ' '.$token->value;
        }

        $this->document->setTitle(trim($title));
        $this->extractValue('}');
    }

    protected function _extractAuthor() {
        $this->extractValue('{');
        $author = '';

        while($token = $this->extractIfMatch('word')) {
            $author .= ' '.$token->value;
        }

        $this->document->setTitle(trim($author));
        $this->extractValue('}');
    }

    protected function _extractDocDate() {
        $this->extractValue('{');

        if($this->token->is('command=today')) {
            $this->document->setDate('now');
            $this->extract();
        } else {
            $date = '';

            while(!$this->token->isValue('}')) {
                $token = $this->extract();

                if($token->isAfterWhitespace()) {
                    $date .= ' ';
                }

                $date .= $token->value;
            }

            $this->document->setDate(trim($date));
        }

        $this->extractValue('}');
    }



    protected function _extractDocument() {}
}