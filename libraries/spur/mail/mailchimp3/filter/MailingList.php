<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailchimp3\filter;

use df\spur;

class MailingList extends Base implements spur\mail\mailchimp3\IListFilter
{
    use TFilter_Directional;

    public const KEY_NAME = 'lists';

    public function toArray(): array
    {
        $output = parent::toArray();

        $this->_applyDirection($output);
        return $output;
    }
}
