<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\agent;

use df;
use df\core;
use df\apex;
use df\axis;

class Unit extends axis\unit\Table {

    const BROADCAST_HOOK_EVENTS = false;

    protected function createSchema($schema) {
        $schema->addPrimaryField('id', 'Guid');
        $schema->addUniqueField('body', 'Text', 255);
        $schema->addField('isBot', 'Boolean');
    }


    public function logAgent($agent) {
        if(empty($agent)) {
            return null;
        }

        $output = $this->fetch()
            ->where('body', '=', $agent)
            ->toRow();

        if(!$output) {
            $output = $this->newRecord([
                'body' => $agent,
                'isBot' => $this->isBot($agent)
            ])->save();
        }

        return $output;
    }

    public function logCurrent() {
        return $this->logAgent($this->getCurrentString());
    }


    public function getCurrentString() {
        $userAgent = null;

        try {
            $runner = df\Launchpad::$runner;

            if($runner instanceof core\app\runner\Http) {
                $userAgent = $runner->getContext()->http->getUserAgent();
            } else if($runner instanceof core\app\runner\Task) {
                if(isset($_SERVER['TERM'])) {
                    $userAgent = $_SERVER['TERM'];
                } else if(isset($_SERVER['TERM_PROGRAM'])) {
                    $userAgent = $_SERVER['TERM_PROGRAM'];
                } else if(isset($_SERVER['TERMINAL'])) {
                    $userAgent = $_SERVER['TERMINAL'];
                } else {
                    $userAgent = 'Terminal';
                }
            }
        } catch(\Throwable $e) {}

        return $userAgent;
    }

    protected $_botMatch = [
        'AddThis.com',
        'AdsBot-Google',
        'AhrefsBot',
        'Apache-HttpClient',
        'Baiduspider',
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
        'NerdyBot',
        'nutch',
        'Protocol Discovery',
        'python-requests',
        'RU_Bot',
        'SafeDNS',
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
        'Yandex',
        'YisouSpider'
    ];

    public function isBot($agent) {
        foreach($this->_botMatch as $match) {
            if(stristr($agent, $match)) {
                return true;
            }
        }

        return false;
    }
}
