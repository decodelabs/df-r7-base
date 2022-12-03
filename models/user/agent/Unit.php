<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\user\agent;

use DecodeLabs\Disciple;

use df\axis;

class Unit extends axis\unit\Table
{
    public const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema)
    {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addUniqueField('body', 'Text', 255);
        $schema->addField('isBot', 'Boolean');
    }

    public function logAgent(?string $agent, bool $logBots = true): array
    {
        $isBot = $this->isBot($agent);
        $shouldLog = !$isBot || $logBots;
        $output = null;

        if ($shouldLog) {
            $output = $this->select()
                ->where('body', '=', $agent)
                ->toRow();
        }

        if (!$output) {
            if ($shouldLog) {
                $id = $this->insert([
                        'body' => $agent,
                        'isBot' => $isBot
                    ])
                    ->ifNotExists(true)
                    ->execute()['id'];
            } else {
                $id = null;
            }

            $output = [
                'id' => $id,
                'body' => $agent,
                'isBot' => $isBot
            ];
        }

        return $output;
    }

    public function logCurrent(bool $logBots = true): array
    {
        return $this->logAgent($this->getCurrentString(), $logBots);
    }


    public function getCurrentString()
    {
        return Disciple::getClient()->getAgent();
    }

    protected $_botMatch = [
        '2345Explorer',
        'AddThis.com',
        'AdsBot-Google',
        'AhrefsBot',
        'Apache-HttpClient',
        'Applebot',
        'Baiduspider',
        'Barkrowler',
        'bingbot',
        'bitlybot',
        'Chilkat',
        'cliqzbot',
        'CRAZYWEBCRAWLER',
        'crawler',
        'curl',
        'DarcyRipper',
        'df-link',
        'Domain Re-Animator',
        'DomainCrawler',
        'DotBot',
        'exabot',
        'facebookexternalhit',
        'Feedly',
        'git',
        'Go-http-client',
        'Googlebot',
        'heritrix',
        'HyperCrawl',
        'istellabot',
        'JOC Web Spider',
        'LinkedInBot',
        'LinkWalker',
        'LSSRocketCrawler',
        'Mediatoolkitbot',
        'MJ12bot',
        'msnbot',
        'Neevabot',
        'NerdyBot',
        'nutch',
        'Protocol Discovery',
        'python-requests',
        'Qwantify',
        'RU_Bot',
        'SafeDNS',
        'SemrushBot',
        'SeznamBot',
        'Slurp',
        'Sogou',
        'spbot',
        'spider',
        'Twitterbot',
        'TurnitinBot',
        'XoviBot',
        'VeriCiteCrawler',
        'woobot',
        'yacybot',
        'Yandex',
        'YisouSpider'
    ];

    public function isBot(?string $agent): bool
    {
        if (empty($agent)) {
            return true;
        }

        foreach ($this->_botMatch as $match) {
            if (stristr($agent, $match)) {
                return true;
            }
        }

        return false;
    }
}
