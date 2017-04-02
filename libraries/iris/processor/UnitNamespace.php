<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\processor;

use df;
use df\core;
use df\iris;

class UnitNamespace extends Base {

    protected $_keyword = 'namespace';
    protected $_separator = '.';
    protected $_regex = null;

    protected $_shortcutKeyword = 'use';
    protected $_aliasKeyword = 'as';
    protected $_allowTypeAliases = true;
    protected $_allowRoot = false;

    public function __construct($keyword=null, $separator=null, $shortcutKeyword=null) {
        if($keyword !== null) {
            $this->setKeyword($keyword);
        }

        if($separator !== null) {
            $this->setSeparator($separator);
        }

        if($shortcutKeyword !== null) {
            $this->setShortcutKeyword($shortcutKeyword);
        }
    }

    public function setKeyword($keyword) {
        $this->_keyword = (string)$keyword;
        return $this;
    }

    public function getKeyword() {
        return $this->_keyword;
    }

    public function setSeparator($separator) {
        $this->_separator = (string)$separator;
        return $this;
    }

    public function getSeparator() {
        return $this->_separator;
    }

    public function setComponentRegex($regex) {
        $this->_regex = $regex;
        return $this;
    }

    public function getComonentRegex() {
        return $this->_regex;
    }


    public function setShortcutKeyword($keyword) {
        $this->_shortcutKeyword = (string)$keyword;
        return $this;
    }

    public function getShortcutKeyword() {
        return $this->_shortcutKeyword;
    }

    public function setAliasKeyword($keyword) {
        $this->_aliasKeyword = (string)$keyword;
        return $this;
    }

    public function getAliasKeyword() {
        return $this->_aliasKeyword;
    }

    public function shouldAllowTypeAliases(bool $flag=null) {
        if($flag !== null) {
            $this->_allowTypeAliases = $flag;
            return $this;
        }

        return $this->_allowTypeAliases;
    }

    public function shouldAllowRoot(bool $flag=null) {
        if($flag !== null) {
            $this->_allowRoot = $flag;
            return $this;
        }

        return $this->_allowRoot;
    }


// Processor
    public function initialize(iris\IParser $parser) {
        parent::initialize($parser);

        if(!$parser->getProcessor('Type')) {
            throw new iris\LogicException(
                'Namespace processor is dependent on Type processor'
            );
        }
    }


    public function processRootDeclaration() {
        $comment = $this->parser->getLastCommentBody();

        if($this->parser->token->matches('keyword', null, $this->_shortcutKeyword)) {
            if(!$this->_allowRoot) {
                throw new iris\UnexpectedTokenException(
                    'Root namespace is not available'
                );
            }

            $namespace = iris\map\aspect\EntityNamespace::root($this->token);
        } else {
            $this->parser->extractMatch('keyword', null, $this->_keyword);
            $namespace = $this->extractNamespace();
            $this->parser->extractStatementEnd();
        }

        $declaration = $this->_newDeclaration($namespace);
        $declaration->setComment($comment);

        $this->parser->currentNamespace = $declaration;
        $this->extractShortcuts($declaration);

        return $declaration;
    }

    public function processBlockDeclaration(callable $bodyHandler) {
        $comment = $this->parser->getLastCommentBody();

        $this->parser->extractMatch('keyword', null, $this->_keyword);

        $namespace = $this->extractNamespace();
        $declaration = $this->_newDeclaration($namespace);
        $declaration->setComment($comment);

        $this->extractMatch('symbol', null, '{');

        $this->parser->currentNamespace = $declaration;
        $this->extractShortcuts($declaration);

        $bodyHandler();

        $this->extractMatch('symbol', null, '}');
        return $this;
    }

    protected function _newDeclaration(iris\map\aspect\EntityNamespace $namespace) {
        return new iris\map\NamespaceDeclaration($namespace->getLocation(), $namespace);
    }

    public function extractNamespace() {
        $output = $this->newNamespace($this->extractNameComponent());

        while($this->parser->token->matches('symbol', null, $this->_separator)) {
            $this->parser->extract();
            $output->push($this->extractNameComponent());
        }

        return $output;
    }

    public function extractTypeNamespace() {
        $output = null;

        while(true) {
            if(!$this->parser->peek(1)->matches('symbol', null, $this->_separator)) {
                break;
            }

            $name = $this->extractNameComponent();

            if(!$output) {
                $output = $this->newNamespace($name);
            } else {
                $output->push($name);
            }

            $this->parser->extract();
        }

        return $output;
    }

    public function newNamespace($name=null) {
        if($name === null) {
            if($this->parser->currentNamespace) {
                $output = $this->parser->currentNamespace->getNamespace()->duplicate($this->parser->token);
            } else if($this->_allowRoot) {
                return iris\map\aspect\EntityNamespace::root($this->parser->token);
            } else {
                throw new iris\UnexpectedTokenException(
                    'Cannot extract base namespace name',
                    $this->parser->token
                );
            }
        }


        if($this->parser->currentNamespace && ($output = $this->parser->currentNamespace->getNamespaceShortcut($name))) {
            $output = $output->duplicate($this->parser->token);
        } else {
            $output = new iris\map\aspect\EntityNamespace($this->parser->token);
            $output->push($name);
        }

        return $output;
    }

    public function extractNameComponent() {
        $token = $this->parser->extractMatch('word');

        if(!$this->testNameComponent($token->value)) {
            throw new iris\UnexpectedTokenException(
                $token->value.' is not a valid namespace name',
                $token
            );
        }

        return $token->value;
    }

    public function testNameComponent($name) {
        if($this->_regex && !preg_match($this->_regex, $name)) {
            return false;
        }

        return true;
    }

    public function extractShortcuts(iris\map\NamespaceDeclaration $declaration=null) {
        if($declaration === null) {
            $declaration = $this->parser->currentNamespace;

            if($declaration === null) {
                throw new iris\UnexpectedTokenException(
                    'No declaration has been defined for namespace shortcuts',
                    $this->token
                );
            }
        }

        $typeProcessor = $this->parser->type;

        while($this->parser->extractIfMatch('keyword', null, $this->_shortcutKeyword)) {
            $type = null;
            $namespace = $this->extractTypeNamespace();

            if($this->parser->peek(1)->matches('symbol', null, $typeProcessor->getContextSeparator())) {
                // Context hinted type
                $type = $typeProcessor->extractTypeName($namespace, $typeProcessor::CONTEXT_CLASS);
                $namespace = null;
            } else {
                $isValidNamespace = $this->testNameComponent($this->parser->token->value);
                $isValidType = $typeProcessor->testName($this->parser->token->value);

                if($isValidType) {
                    $type = $typeProcessor->extractTypeName($namespace, $typeProcessor::CONTEXT_CLASS);
                }

                if($isValidNamespace && !$namespace) {
                    $namespace = new iris\map\aspect\EntityNamespace($this->parser->token);
                }

                if($isValidNamespace && $isValidType) {
                    // Could be either
                    $namespace = clone $namespace;
                    $namespace->push($type->getName());
                } else if($isValidNamespace) {
                    // Only namespace
                    $namespace->push($this->parser->extractWord()->value);
                } else if($isValidType) {
                    // Only type
                    $namespace = null;
                }
            }


            // Alias
            if($keywordToken = $this->parser->extractIfMatch('keyword', null, $this->_aliasKeyword)) {
                if($type && !$this->_allowTypeAliases) {
                    $type = null;

                    if($namespace === null) {
                        throw new iris\UnexpectedTokenException(
                            'Type aliasing is not allowed',
                            $keywordToken
                        );
                    }
                }

                $aliasToken = $this->parser->extractWord();

                if($namespace) {
                    $declaration->addNamespaceShortcut($aliasToken->value, $namespace);
                }

                if($type) {
                    $declaration->addTypeShortcut($aliasToken->value, $type);
                }
            } else {
                if($namespace) {
                    $declaration->addNamespaceShortcut($namespace->getLast(), $namespace);
                }

                if($type) {
                    $declaration->addTypeShortcut($type->getName(), $type);
                }
            }

            $this->parser->extractStatementEnd();
        }
    }
}