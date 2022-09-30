<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\navigation\breadcrumbs;

use df;
use df\core;
use df\arch;

use DecodeLabs\Genesis;

class EntryList implements arch\navigation\IEntryList, core\IRegistryObject, core\IDispatchAware
{
    use arch\navigation\TEntryList;

    public const REGISTRY_KEY = 'breadcrumbs';

    public function getRegistryObjectKey(): string
    {
        return self::REGISTRY_KEY;
    }

    public function onAppDispatch(arch\node\INode $node): void
    {
        df\Launchpad::$app->removeRegistryObject(self::REGISTRY_KEY);
    }

    public static function generateFromRequest(arch\IRequest $request)
    {
        $output = new self();
        $parts = $request->getLiteralPathArray();
        $path = '';

        if (false !== strpos($last = array_pop($parts), '.')) {
            $lastParts = explode('.', $last);
            $last = array_shift($lastParts);
        }

        $parts[] = $last;

        if ($request->isDefaultArea()) {
            array_shift($parts);
        }

        $isDefaultNode = false;

        if ($request->isDefaultNode()) {
            array_pop($parts);
            $isDefaultNode = true;
        }

        $count = count($parts);

        foreach ($parts as $i => $part) {
            if (!$isDefaultNode && $i == $count - 1) {
                $path .= $part;
            } else {
                $path .= $part.'/';
            }

            $title = $part;

            if ($i == 0) {
                $title = ltrim($title, $request::AREA_MARKER);
            }

            $title = ucwords(
                (string)preg_replace('/([A-Z])/u', ' $1', str_replace(
                    ['-', '_'], ' ', (string)$title
                ))
            );

            if ($i == $count - 1) {
                $linkRequest = $request;
            } else {
                $linkRequest = arch\Request::factory($path);
            }

            $output->addEntry(
                (new arch\navigation\entry\Link($linkRequest, $title))
                    ->setId($path)
                    ->setWeight(($i + 1) * 10)
            );
        }

        return $output;
    }
}
