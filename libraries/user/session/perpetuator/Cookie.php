<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\session\perpetuator;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Legacy;
use df\arch;
use df\axis;

use df\link;
use df\user;

class Cookie implements user\session\IPerpetuator
{
    public const SESSION_NAME = '_s';
    public const REMEMBER_NAME = '_r';
    public const JOIN_NAME = '_j';
    public const STATE_NAME = '_a';

    protected $_inputId;
    protected $_canRecall = true;

    public function __construct()
    {
        $cookies = Legacy::$http->getCookies();
        $router = Legacy::$http->getRouter();
        $isRoot = $router->isBaseInRoot();

        if (!$isRoot && !$cookies->has(self::SESSION_NAME) && !$cookies->has(self::JOIN_NAME)) {
            $this->_joinRoot();
        }

        if ($cookies->has(self::JOIN_NAME)) {
            try {
                $key = hex2bin($cookies->get(self::JOIN_NAME));
                $this->_inputId = $this->_consumeJoinKey($key);
            } catch (\Throwable $e) {
                //$cookie = $cookies->get(self::JOIN_NAME);
                $cookies->remove(self::JOIN_NAME);

                /*
                throw Exceptional::UnexpectedValue([
                    'message' => 'Invalid join cookie: "'.$cookie.'"',
                    'previous' => $e
                ]);
                 */
            }
        }

        if ($this->_inputId === null && $cookies->has(self::SESSION_NAME)) {
            try {
                $this->_inputId = hex2bin($cookies->get(self::SESSION_NAME));
            } catch (\Throwable $e) {
                $this->_inputId = null;
            }
        }

        if ($cookies->has(self::REMEMBER_NAME) && $cookies->get(self::REMEMBER_NAME) == '') {
            $this->_canRecall = false;
        }
    }

    public function getInputId()
    {
        return $this->_inputId;
    }

    public function canRecallIdentity()
    {
        return $this->_canRecall;
    }

    public function perpetuate(user\session\IController $controller, user\session\Descriptor $descriptor)
    {
        $outputId = $descriptor->getPublicKeyHex();

        if ($outputId != $this->_inputId) {
            $this->_setSessionCookie($outputId);
        }

        return $this;
    }

    protected function _setSessionCookie($outputId)
    {
        $augmentor = Legacy::$http->getResponseAugmentor();
        $augmentor->setCookieForAnyRequest($augmentor->newCookie(
            self::SESSION_NAME,
            $outputId,
            null,
            true
        ));
    }

    public function destroy(user\session\IController $controller)
    {
        $augmentor = Legacy::$http->getResponseAugmentor();

        // Remove session cookie
        $augmentor->removeCookieForAnyRequest($augmentor->newCookie(
            self::SESSION_NAME,
            'deleted',
            null,
            true
        ));

        // Set remember cookie to ''
        $augmentor->setCookieForAnyRequest($augmentor->newCookie(
            self::REMEMBER_NAME,
            '',
            null,
            true
        ));

        return $this;
    }

    public function handleDeadPublicKey($publicKey)
    {
        $cookies = Legacy::$http->getCookies();
        $isRoot = Legacy::$http->getRouter()->isBaseRoot();

        if (!$isRoot && !$cookies->has(self::JOIN_NAME)) {
            $this->_joinRoot();
        }
    }

    protected function _joinRoot()
    {
        $key = $this->_generateJoinKey();
        $this->setJoinKey($key);

        $httpRequest = Legacy::$http->getRequest();
        $router = Legacy::$http->getRouter();

        $request = $router->requestToUrl(arch\Request::factory('account/join-session?key=' . bin2hex($key)));
        $request->query->rf = $router->urlToRequest($httpRequest->getUrl())->encode();
        $redirect = new link\http\response\Redirect($request);

        throw new arch\ForcedResponse($redirect);
    }

    public function perpetuateRecallKey(user\session\IController $controller, user\session\RecallKey $key)
    {
        $augmentor = Legacy::$http->getResponseAugmentor();

        $augmentor->setCookieForAnyRequest($augmentor->newCookie(
            self::REMEMBER_NAME,
            $key->getInterlaceKey(),
            '+1 month',
            true
        ));

        return $this;
    }

    public function getRecallKey(user\session\IController $controller)
    {
        $httpRequest = Legacy::$http->getRequest();

        if (!$httpRequest->hasCookieData()) {
            return null;
        }

        $value = $httpRequest->getCookies()->get(self::REMEMBER_NAME);

        if (!empty($value)) {
            return new user\session\RecallKey(
                substr($value, 20, 1),
                substr($value, 0, 20) . substr($value, 21)
            );
        }
    }

    public function destroyRecallKey(user\session\IController $controller)
    {
        $augmentor = Legacy::$http->getResponseAugmentor();

        $augmentor->removeCookieForAnyRequest($augmentor->newCookie(
            self::REMEMBER_NAME,
            '',
            null,
            true
        ));

        return $this;
    }


    public function setJoinKey($key)
    {
        $augmentor = Legacy::$http->getResponseAugmentor();

        $augmentor->setCookieForAnyRequest($augmentor->newCookie(
            self::JOIN_NAME,
            bin2hex($key),
            '+3 minutes',
            true
        ));

        return $this;
    }

    public function destroyJoinKey()
    {
        $augmentor = Legacy::$http->getResponseAugmentor();

        $augmentor->removeCookieForAnyRequest($augmentor->newCookie(
            self::JOIN_NAME,
            '',
            null,
            true
        ));

        return $this;
    }

    protected function _generateJoinKey()
    {
        return axis\Model::loadUnitFromId('session/stub')->generateKey();
    }

    protected function _consumeJoinKey($key)
    {
        $output = axis\Model::loadUnitFromId('session/descriptor')->select('publicKey')
            ->whereCorrelation('id', '=', 'sessionId')
                ->from('axis://session/Stub', 'stub')
                ->where('key', '=', $key)
                ->endCorrelation()
            ->toValue('publicKey');

        if ($output !== null) {
            axis\Model::loadUnitFromId('session/stub')->delete()
                ->where('key', '=', $key)
                ->execute();

            $this->_setSessionCookie(bin2hex($output));
        }

        $this->destroyJoinKey();
        return $output;
    }


    public function perpetuateState(
        user\IClient $client
    ) {
        $sigs = $client->getSignifiers();

        if (in_array('guest', $sigs)) {
            $sigs = [];
        }

        $augmentor = Legacy::$http->getResponseAugmentor();

        if (
            array_key_exists(self::STATE_NAME, $_COOKIE) &&
            empty($sigs)
        ) {
            $augmentor->removeCookieForAnyRequest($augmentor->newCookie(
                self::STATE_NAME,
                '',
                null,
                true
            ));
        }

        if (empty($sigs)) {
            return;
        }

        $value = [];

        foreach ($sigs as $sig) {
            $value[] = md5($client->getId() . ':' . $sig);
        }

        $value = implode('.', $value);

        if (
            array_key_exists(self::STATE_NAME, $_COOKIE) &&
            $_COOKIE[self::STATE_NAME] == $value
        ) {
            return;
        }


        $augmentor->setCookieForAnyRequest($augmentor->newCookie(
            self::STATE_NAME,
            $value,
            '+5 minutes',
            true
        ));
    }
}
