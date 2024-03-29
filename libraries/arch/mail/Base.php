<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\mail;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use df\arch;

use df\aura;
use df\core;

abstract class Base extends aura\view\Mail implements arch\IMail
{
    public const TEMPLATE = true;
    public const SUBJECT = null;
    public const DESCRIPTION = null;

    public const JOURNAL_WEEKS = 10; // weeks

    public static function factory(arch\IContext $context, $path): arch\IMail
    {
        $origPath = $path;
        $location = $context->extractDirectoryLocation($path);
        $parts = $location->getControllerParts();
        $parts[] = '_mail';
        $parts[] = str_replace('/', '\\', $path);

        $class = 'df\\apex\\directory\\' . $location->getArea() . '\\' . implode('\\', $parts);

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Mail ' . $origPath . ' could not be found'
            );
        }

        return new $class($context->spawnInstance($location));
    }

    public function __construct(arch\IContext $context)
    {
        parent::__construct('Mail', $context);

        if (static::SUBJECT !== null) {
            $this->setSubject(static::SUBJECT);
        }
    }

    public function getName(): string
    {
        $path = str_replace('\\', '/', get_class($this));
        $parts = explode('_mail/', $path, 2);
        return (string)array_pop($parts);
    }

    public function getDescription()
    {
        $output = static::DESCRIPTION;

        if (empty($output) && !empty(static::SUBJECT)) {
            $output = static::SUBJECT;
        }

        if (empty($output)) {
            $output = Dictum::name($this->getName());
        }

        return $output;
    }


    // Rendering
    public function preparePreview()
    {
    }
    public function execute()
    {
    }


    protected function _beforeRender()
    {
        parent::_beforeRender();

        if (!$this->content) {
            if (static::TEMPLATE) {
                if (is_string(static::TEMPLATE)) {
                    $template = static::TEMPLATE;
                } else {
                    $template = '#' . $this->getName() . '.mail';
                }

                $this->content = $this->context->apex->template($template);
            } else {
                $this->content = new aura\view\content\WidgetContentProvider($this->context);
            }
        }

        $this->execute();
    }


    // Journal
    public function getJournalName()
    {
        if ($this->_journalName === null) {
            $this->_journalName = $this->_getDefaultJournalName();
        }

        return $this->_journalName;
    }

    protected function _getDefaultJournalName()
    {
        $output = '~' . $this->context->location->getDirectoryLocation();
        $name = $this->getName();

        if (false !== strpos($name, '/')) {
            $output = rtrim($output, '/') . '/#';
        }

        $output .= '/' . $name;

        if (0 === strpos($output, '~front/')) {
            $output = substr($output, 7);
        }

        return $output;
    }

    public function getJournalDuration()
    {
        if (!$this->_journalDuration) {
            $weeks = (int)static::JOURNAL_WEEKS;

            if ($weeks <= 0) {
                $weeks = 26;
            }

            $this->_journalDuration = core\time\Duration::fromWeeks($weeks);
        }

        return parent::getJournalDuration();
    }

    public function shouldJournal(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldJournal = $flag;
            return $this;
        }

        return $this->_shouldJournal;
    }
}
