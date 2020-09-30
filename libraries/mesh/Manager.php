<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh;

use df;
use df\core;
use df\mesh;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Exceptional;

class Manager implements IManager, Dumpable
{
    use core\TManager;
    use mesh\event\TEmitter;

    const REGISTRY_PREFIX = 'manager://mesh';

    protected $_handlers = [];

    // Jobs
    public function newJobQueue()
    {
        return new mesh\job\Queue();
    }

    // Entities
    public function registerHandler($scheme, IHandler $handler)
    {
        $this->_handlers[(string)$scheme] = $handler;

        return $this;
    }

    public function unregisterHandler($scheme)
    {
        unset($this->_handlers[(string)$scheme]);
        return $this;
    }

    public function getHandler($scheme)
    {
        if ($scheme instanceof mesh\entity\ILocator) {
            $scheme = $scheme->getScheme();
        } else {
            $scheme = (string)$scheme;

            if (false !== strpos($scheme, ':')) {
                $scheme = mesh\entity\Locator::factory($scheme);
                $scheme = $scheme->getScheme();
            }
        }

        if (!isset($this->_handlers[$scheme])) {
            $classes = [
                'df\\'.lcfirst($scheme).'\\MeshHandler',
                'df\\mesh\\handler\\'.ucfirst($scheme)
            ];

            foreach ($classes as $class) {
                if (class_exists($class)) {
                    $handler = new $class();
                    $this->registerHandler($scheme, $handler);
                    return $handler;
                }
            }

            if (!isset($this->_handlers[$scheme])) {
                return null;
            }
        }

        return $this->_handlers[$scheme];
    }

    public function getHandlers()
    {
        return $this->_handlers;
    }

    public function fetchEntity($locator)
    {
        $locator = mesh\entity\Locator::factory($locator);

        if ((!$handler = $this->getHandler($locator))
        || (!$handler instanceof IEntityHandler)) {
            throw Exceptional::Runtime(
                'There is no entity handler for scheme: '.$locator->getScheme()
            );
        }


        $nodes = $locator->getNodes();
        $node = array_shift($nodes);

        $entity = $handler->fetchEntity($this, $node);

        if ($entity === null) {
            throw Exceptional::{'df/mesh/entity/NotFound'}(
                'Entity type '.$locator->toStringUpTo($node).' could not be found'
            );
        }

        if (!empty($nodes)) {
            $lastNode = $node;

            foreach ($nodes as $node) {
                if (!$entity instanceof mesh\entity\IParentEntity) {
                    throw Exceptional::{'df/mesh/entity/NotFound'}(
                        'Could not load entity '.$locator->toString().' - '.
                        'parent entity '.$locator->toStringUpTo($lastNode).' does not provide sub entities'
                    );
                }

                $entity = $entity->fetchSubEntity($this, $node);

                if ($entity === null) {
                    throw Exceptional::{'df/mesh/entity/NotFound'}(
                        'Entity type '.$locator->toStringUpTo($node).' could not be found'
                    );
                }

                $lastNode = $node;
            }
        }

        return $entity;
    }



    // Events
    public function emitEventObject(mesh\event\IEvent $event)
    {
        mesh\event\Hook::triggerEvent($event);
        return $this;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        yield 'property:%handlers' => implode(', ', array_keys($this->_handlers));
    }
}
