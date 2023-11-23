<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\mail\mailchimp3;

use DecodeLabs\Compass\Ip;
use df\core;
use df\flex;
use df\link;
use df\spur;

use df\user;
use Psr\Http\Message\ResponseInterface;

class Mediator implements IMediator
{
    use spur\TGuzzleMediator;

    public const API_URL = '//api.mailchimp.com/3.0/';

    protected $_isSecure = true;
    protected $_apiKey;
    protected $_dataCenter = 'us1';
    protected $_activeUrl;

    public function __construct(string $apiKey, bool $secure = true)
    {
        $this->setApiKey($apiKey);
        $this->isSecure($secure);
    }


    // Transport
    public function isSecure(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isSecure = $flag;
            return $this;
        }

        return $this->_isSecure;
    }

    public function canConnect(): bool
    {
        try {
            $data = $this->requestJson('get', '/', ['fields' => 'account_id']);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }



    // Api key
    public function setApiKey(string $key)
    {
        $this->_apiKey = $key;

        if (strstr($key, '-')) {
            $parts = explode('-', $key, 2);
            $key = array_shift($parts);

            if ($dataCenter = array_shift($parts)) {
                $this->_dataCenter = $dataCenter;
            }
        }

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->_apiKey;
    }

    public function getDataCenterId(): string
    {
        return $this->_dataCenter;
    }



    // Account
    public function getAccountDetails(): IDataObject
    {
        $data = $this->requestJson('get', '/', ['exclude_fields' => '_links']);
        return new DataObject('account', $data);
    }

    public function getApiLinks(): IDataObject
    {
        $data = $this->requestJson('get', '/', ['fields' => '_links']);
        return new DataObject('links', $data->_links);
    }



    // Lists
    public function fetchList(string $id): IDataObject
    {
        $data = $this->requestJson('get', 'lists/' . $id, ['exclude_fields' => '_links']);
        return new DataObject('list', $data, [$this, '_processList']);
    }


    public function newListFilter(): IListFilter
    {
        return new spur\mail\mailchimp3\filter\MailingList();
    }

    public function fetchLists(IListFilter $filter = null): IDataList
    {
        $data = $this->requestJson(
            'get',
            'lists',
            spur\mail\mailchimp3\filter\MailingList::normalize($filter)
        );

        return new DataList('list', $filter, $data, [$this, '_processList']);
    }

    protected function _processList(core\collection\ITree $data)
    {
        if (isset($data->date_created)) {
            $data['date_created'] = core\time\Date::normalize($data['date_created']);
        }

        if (isset($data->stats->last_unsub_date)) {
            $data->stats['last_unsub_date'] = core\time\Date::normalize($data->stats['last_unsub_date']);
        }
    }





    // Interest categories
    public function fetchInterestCategory(string $listId, string $categoryId): IDataObject
    {
        $data = $this->requestJson('get', 'lists/' . $listId . '/interest-categories/' . $categoryId, ['exclude_fields' => '_links']);
        return new DataObject('interest-category', $data);
    }

    public function newInterestCategoryFilter(): IInterestCategoryFilter
    {
        return new spur\mail\mailchimp3\filter\InterestCategory();
    }

    public function fetchInterestCategories(string $listId, IInterestCategoryFilter $filter = null): IDataList
    {
        $data = $this->requestJson(
            'get',
            'lists/' . $listId . '/interest-categories',
            spur\mail\mailchimp3\filter\InterestCategory::normalize($filter)
        );

        return new DataList('interest-category', $filter, $data);
    }



    // Interests
    public function fetchInterest(string $listId, string $categoryId, string $interestId): IDataObject
    {
        $data = $this->requestJson('get', 'lists/' . $listId . '/interest-categories/' . $categoryId . '/interests/' . $interestId, ['exclude_fields' => '_links']);
        return new DataObject('interest', $data);
    }


    public function newInterestFilter(): IInterestFilter
    {
        return new spur\mail\mailchimp3\filter\Interest();
    }

    public function fetchInterests(string $listId, string $categoryId, IInterestFilter $filter = null): IDataList
    {
        $data = $this->requestJson(
            'get',
            'lists/' . $listId . '/interest-categories/' . $categoryId . '/interests',
            spur\mail\mailchimp3\filter\Interest::normalize($filter)
        );

        return new DataList('interest', $filter, $data);
    }



    // Members
    public function fetchMember(string $listId, string $email): IDataObject
    {
        return $this->fetchMemberByHash($listId, md5(strtolower($email)));
    }

    public function fetchMemberByHash(string $listId, string $hash): IDataObject
    {
        $data = $this->requestJson('get', 'lists/' . $listId . '/members/' . $hash, ['exclude_fields' => '_links']);
        return new DataObject('member', $data, [$this, '_processMember']);
    }


    public function newMemberFilter(): IMemberFilter
    {
        return new spur\mail\mailchimp3\filter\Member();
    }

    public function fetchMembers(string $listId, IMemberFilter $filter = null): IDataList
    {
        $data = $this->requestJson(
            'get',
            'lists/' . $listId . '/members',
            spur\mail\mailchimp3\filter\Member::normalize($filter)
        );

        return new DataList('member', $filter, $data, [$this, '_processMember']);
    }


    public function ensureSubscription(
        string $listId,
        user\IClientDataObject $user,
        array $groups = [],
        ?array $extraData = null,
        ?array $tags = null
    ): IDataObject {
        $input = [
            'email_address' => $email = $user->getEmail(),
            'status_if_new' => 'subscribed',
            'status' => 'subscribed',
            'exclude_fields' => '_links'
        ];

        if (!empty($groups)) {
            $input['interests'] = $groups;
        }

        if (null !== ($firstName = $user->getFirstName())) {
            $input['merge_fields']['FNAME'] = $firstName;
        }

        if (null !== ($surname = $user->getSurname())) {
            $input['merge_fields']['LNAME'] = $surname;
        }

        if ($user->getId()) {
            if (null !== ($country = $user->getCountry())) {
                $input['merge_fields']['COUNTRY'] = $country;
            }

            if (null !== ($language = $user->getLanguage())) {
                $input['language'] = $language;
            }
        }

        foreach ($extraData ?? [] as $key => $value) {
            $input['merge_fields'][strtoupper($key)] = $value;
        }

        if ($tags !== null) {
            $input['tags'] = array_values(array_map('strval', $tags));
        }

        $hash = md5($email);
        $data = $this->requestJson('put', 'lists/' . $listId . '/members/' . $hash, $input);
        return new DataObject('member', $data, [$this, '_processMember']);
    }

    public function unsubscribe(string $listId, string $email): ?IDataObject
    {
        $hash = md5($email);

        try {
            $data = $this->requestJson('patch', 'lists/' . $listId . '/members/' . $hash, [
                'exclude_fields' => '_links',
                'status' => 'unsubscribed'
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        return new DataObject('member', $data, [$this, '_processMember']);
    }


    public function updateMemberDetails(string $listId, string $oldEmail, user\IClientDataObject $user): IDataObject
    {
        $hash = md5(strtolower($oldEmail));
        $input = [
            'exclude_fields' => '_links'
        ];

        if (null !== ($email = $user->getEmail())) {
            $input['email_address'] = $email;
        }

        if (null !== ($firstName = $user->getFirstName())) {
            $input['merge_fields']['FNAME'] = $firstName;
        }

        if (null !== ($surname = $user->getSurname())) {
            $input['merge_fields']['LNAME'] = $surname;
        }

        if ($user->getId()) {
            if (null !== ($country = $user->getCountry())) {
                $input['merge_fields']['COUNTRY'] = $country;
            }

            if (null !== ($language = $user->getLanguage())) {
                $input['language'] = $language;
            }
        }

        $data = $this->requestJson('patch', 'lists/' . $listId . '/members/' . $hash, $input);
        return new DataObject('member', $data, [$this, '_processMember']);
    }



    public function deleteMember(string $listId, string $email)
    {
        $hash = md5(strtolower($email));
        $this->requestRaw('delete', 'lists/' . $listId . '/members/' . $hash);
        return $this;
    }


    protected function _processMember(core\collection\ITree $data)
    {
        if (isset($data->ip_signup)) {
            $data['ip_signup'] = Ip::orNull($data['ip_signup']);
        }

        if (isset($data->timestamp_signup)) {
            $data['timestamp_signup'] = core\time\Date::normalize($data['timestamp_signup']);
        }

        if (isset($data->ip_opt)) {
            $data['ip_opt'] = Ip::orNull($data['ip_opt']);
        }

        if (isset($data->timestamp_opt)) {
            $data['timestamp_opt'] = core\time\Date::normalize($data['timestamp_opt']);
        }

        if (isset($data->last_changed)) {
            $data['last_changed'] = core\time\Date::normalize($data['last_changed']);
        }
    }




    // IO
    public function createRequest(string $method, string $path, array $args = [], array $headers = []): link\http\IRequest
    {
        $url = $this->createUrl($path);
        $request = link\http\request\Base::factory($url);
        $request->setMethod($method);

        $isBodyDataMethod = in_array($method, ['post', 'put', 'patch']);

        // Extract filter args
        if ($isBodyDataMethod) {
            if (isset($args['fields'])) {
                $url->query->fields = $args['fields'];
                unset($args['fields']);
            } elseif (isset($args['exclude_fields'])) {
                $url->query->exclude_fields = $args['exclude_fields'];
                unset($args['exclude_fields']);
            }
        }

        // Apply args
        if (!empty($args)) {
            if ($isBodyDataMethod) {
                $request->setBodyData(flex\Json::toString($args));
                $request->headers->set('content-type', 'application/json');
            } else {
                $request->url->query->import($args);
            }
        }

        if (!empty($headers)) {
            $request->headers->import($headers);
        }

        return $request;
    }

    public function createUrl(string $path): link\http\IUrl
    {
        if (!$this->_activeUrl) {
            $this->_activeUrl = link\http\Url::factory(self::API_URL);
            $this->_activeUrl->setDomain($this->_dataCenter . '.' . $this->_activeUrl->getDomain());
            $this->_activeUrl->isSecure($this->_isSecure);
            $this->_activeUrl->setCredentials('x', $this->_apiKey);
        }

        $url = clone $this->_activeUrl;
        $url->path->push($path);

        return $url;
    }

    protected function _extractResponseError(ResponseInterface $response)
    {
        $data = flex\Json::fromString((string)$response->getBody());
        $error = $data['detail'] ?? 'Undefined chimp calamity!';

        if (isset($data['title'])) {
            $error = $data['title'] . ' - ' . $error;
        }

        if (isset($data['status'])) {
            $error = '[' . $data['status'] . '] ' . $error;
        }

        return $error;
    }
}
