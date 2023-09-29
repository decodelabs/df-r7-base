<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\navigation;

use DecodeLabs\Exceptional;
use df\arch;
use df\core;

use df\user;

// Interfaces
interface IEntry extends core\IArrayInterchange
{
    public function getType();

    public function setId(?string $id);
    public function getId(): ?string;

    public function setWeight($weight);
    public function getWeight();
}


/**
 * @method entry\Link newLink($uri, $body, $icon=null)
 * @method entry\Menu newMenu($delegate, $text, $icon=null)
 * @method entry\Spacer newSpacer()
 */
interface IEntryList extends core\IArrayInterchange, \Countable
{
    public function setEntries(...$entries);
    public function addEntries(...$entries);
    public function addEntry($entry);
    public function addLink($uri, $body, $icon = null);
    public function addSpacer();
    public function addMenu($delegate, $text, $icon = null);
    public function getEntry($id);
    public function getEntryByIndex($index);
    public function getLastEntry();
    public function getEntries();
    public function removeEntry($id);
    public function clearEntries();
}

interface IEntryListGenerator
{
    public function generateEntries(IEntryList $entryList = null): arch\navigation\IEntryList;
}


trait TEntryGenerator
{
    public function __call($method, $args)
    {
        $prefix = substr($method, 0, 3);

        if ($prefix == 'new' || $prefix == 'add') {
            $output = arch\navigation\entry\Base::factory(substr($method, 3), ...$args);

            if ($prefix == 'add') {
                $this->addEntry($output);
            }

            return $output;
        }

        throw Exceptional::BadMethodCall(
            'Method ' . $method . ' does not exist'
        );
    }
}

/**
 * @method entry\Link newLink($uri, $body, $icon=null)
 * @method entry\Menu newMenu($delegate, $text, $icon=null)
 * @method entry\Spacer newSpacer()
 */
trait TEntryList
{
    use TEntryGenerator;

    protected $_entries = [];
    protected $_isSorted = false;

    public static function fromArray(array $entries)
    {
        return (new self())->addEntries(...$entries);
    }

    public function setEntries(...$entries)
    {
        return $this->clearEntries()->addEntries(...$entries);
    }

    public function addEntries(...$entries)
    {
        foreach ($entries as $entry) {
            $this->addEntry($entry);
        }

        return $this;
    }

    public function addEntry($entry)
    {
        if (!$entry instanceof IEntry) {
            if (is_array($entry)) {
                $entry = arch\navigation\entry\Base::fromArray($entry);
            } else {
                throw Exceptional::InvalidArgument([
                    'message' => 'Invalid entry definition detected',
                    'data' => $entry
                ]);
            }
        }

        if ($entry->getWeight() == 0) {
            $entry->setWeight(count($this->_entries) + 1);
        }

        $this->_entries[$entry->getId()] = $entry;
        $this->_isSorted = false;

        return $this;
    }

    public function addLink($uri, $body, $icon = null)
    {
        $entry = new arch\navigation\entry\Link($uri, $body, $icon);
        $this->addEntry($entry);
        return $entry;
    }

    public function addSpacer()
    {
        $entry = new arch\navigation\entry\Spacer();
        $this->addEntry($entry);
        return $entry;
    }

    public function addMenu($delegate, $text, $icon = null)
    {
        $entry = new arch\navigation\entry\Menu($delegate, $text, $icon);
        $this->addEntry($entry);
        return $entry;
    }

    public function getEntry($id)
    {
        if (isset($this->_entries[$id])) {
            return $this->_entries[$id];
        }

        return null;
    }

    public function getEntryByIndex($index)
    {
        $index = (int)$index;
        $count = count($this->_entries);

        if ($index < 0) {
            $index += $count;

            if ($index < 0) {
                return null;
            }
        }

        if ($index > $count) {
            return null;
        }

        $t = $this->_entries;

        for ($i = 0; $i < $index; $i++) {
            array_shift($t);
        }

        $output = array_shift($t);
        return $output;
    }

    public function getLastEntry()
    {
        $t = $this->_entries;
        return array_pop($t);
    }

    public function getEntries()
    {
        if (!$this->_isSorted) {
            $this->_sortEntries();
            $this->_isSorted = true;
        }

        return $this->_entries;
    }

    protected function _sortEntries()
    {
        usort($this->_entries, function ($a, $b) {
            return $a->getWeight() <=> $b->getWeight();
        });
    }

    public function removeEntry($id)
    {
        unset($this->_entries[$id]);
        return $this;
    }

    public function clearEntries()
    {
        $this->_entries = [];
        return $this;
    }


    public function toArray(): array
    {
        return $this->getEntries();
    }

    public function count(): int
    {
        return count($this->_entries);
    }
}


interface ILink extends user\IAccessControlled
{
    // Uri
    public function setUri($uri, $setAsMatchRequest = false);
    public function getUri();

    // Body
    public function setBody($body);
    public function getBody();

    // Match request
    public function setMatchRequest($request);
    public function getMatchRequest();

    // Icon
    public function setIcon(string $icon = null);
    public function getIcon();

    // Note
    public function setNote($note);
    public function getNote();

    // Description
    public function setDescription($description);
    public function getDescription();
    public function shouldShowDescription(bool $flag = null);

    // Visibility
    public function shouldHideIfInaccessible(bool $flag = null);

    // Disposition
    public function setDisposition($disposition);
    public function getDisposition();

    // Alt matches
    public function addAltMatches(...$matches);
    public function addAltMatch($match);
    public function getAltMatches();
    public function clearAltMatches();
}

trait TSharedLinkComponents
{
    use user\TAccessControlled;

    protected $_uri;
    protected $_matchRequest;
    protected $_note;
    protected $_description;
    protected $_showDescription = true;
    protected $_hideIfInaccessible = false;
    protected $_altMatches = [];

    // Uri
    public function setUri($uri, $setAsMatchRequest = false)
    {
        $this->_uri = $uri;

        if ($setAsMatchRequest) {
            $this->setMatchRequest($uri);
        }

        return $this;
    }

    public function getUri()
    {
        return $this->_uri;
    }


    // Match request
    public function setMatchRequest($request)
    {
        $this->_matchRequest = $request;
        return $this;
    }

    public function getMatchRequest()
    {
        return $this->_matchRequest;
    }


    public function ensureMatchRequest()
    {
        if ($this->_matchRequest) {
            return $this;
        }

        if ($this->_uri instanceof arch\IRequest) {
            $this->_matchRequest = $this->_uri;
        }

        if (is_string($this->_uri) && substr($this->_uri, 0, 4) != 'http') {
            $this->_matchRequest = $this->_uri;
        }

        return $this;
    }


    // Note
    public function setNote($note)
    {
        $this->_note = $note;
        return $this;
    }

    public function getNote()
    {
        return $this->_note;
    }

    // Description
    public function setDescription($description)
    {
        $this->_description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function shouldShowDescription(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_showDescription = $flag;
            return $this;
        }

        return $this->_showDescription;
    }


    // Visibility
    public function shouldHideIfInaccessible(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_hideIfInaccessible = $flag;
            return $this;
        }

        return $this->_hideIfInaccessible;
    }


    // Alt matches
    public function addAltMatches(...$matches)
    {
        foreach ($matches as $match) {
            $this->addAltMatch($match);
        }

        return $this;
    }

    public function addAltMatch($match)
    {
        $match = trim((string)$match);

        if (strlen($match)) {
            $this->_altMatches[] = $match;
        }

        return $this;
    }

    public function getAltMatches()
    {
        return $this->_altMatches;
    }

    public function clearAltMatches()
    {
        $this->_altMatches = [];
        return $this;
    }

    // IO
    protected function _setSharedLinkComponentData(core\collection\ITree $data)
    {
        return $this
            ->setUri($data['uri'])
            ->setMatchRequest($data['matchRequest'])
            ->setNote($data['note'])
            ->setDescription($data['description'])
            ->shouldShowDescription((bool)$data->get('showDescription', true))
            ->shouldHideIfInaccessible((bool)$data->get('hideIfInaccessible', false))
            ->addAltMatches(...$data->altMatches->toArray())
            ->addAccessLocks($data->accessLocks->toArray())
            ->shouldCheckAccess((bool)$data->get('checkAccess', true));
    }

    protected function _getSharedLinkComponentData()
    {
        return [
            'uri' => $this->_uri,
            'matchRequest' => $this->_matchRequest,
            'note' => $this->_note,
            'description' => $this->_description,
            'showDescription' => $this->_showDescription,
            'hideIfInaccessible' => $this->_hideIfInaccessible,
            'altMatches' => $this->_altMatches,
            'accessLocks' => $this->_accessLocks,
            'checkAccess' => $this->_checkAccess
        ];
    }
}




############
## Sitemap
interface ISitemapEntry
{
    public function setUrl(string $url);
    public function getUrl();
    public function setLastModifiedDate($date);
    public function getLastModifiedDate();
    public function setChangeFrequency($frequency);
    public function getChangeFrequency();
    public function setPriority($priority);
    public function getPriority();
}
