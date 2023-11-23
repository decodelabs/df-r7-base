<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\mail\mailchimp3;

use df\spur;
use df\user;

interface IMediator extends spur\IGuzzleMediator
{
    // Transport
    public function isSecure(bool $flag = null);
    public function canConnect(): bool;

    // Api key
    public function setApiKey(string $key);
    public function getApiKey(): ?string;
    public function getDataCenterId(): string;


    // Account
    public function getAccountDetails(): IDataObject;
    public function getApiLinks(): IDataObject;


    // Lists
    public function fetchList(string $id): IDataObject;

    public function newListFilter(): IListFilter;
    public function fetchLists(IListFilter $filter = null): IDataList;



    // Interest categories
    public function fetchInterestCategory(string $listId, string $categoryId): IDataObject;

    public function newInterestCategoryFilter(): IInterestCategoryFilter;
    public function fetchInterestCategories(string $listId, IInterestCategoryFilter $filter = null): IDataList;


    // Interests
    public function fetchInterest(string $listId, string $categoryId, string $interestId): IDataObject;

    public function newInterestFilter(): IInterestFilter;
    public function fetchInterests(string $listId, string $categoryId, IInterestFilter $filter = null): IDataList;



    // Members
    public function fetchMember(string $listId, string $email): IDataObject;
    public function fetchMemberByHash(string $listId, string $hash): IDataObject;

    public function newMemberFilter(): IMemberFilter;
    public function fetchMembers(string $listId, IMemberFilter $filter = null): IDataList;

    public function ensureSubscription(
        string $listId,
        user\IClientDataObject $user,
        array $groups = [],
        ?array $extraData = null,
        ?array $tags = null
    ): IDataObject;
    public function unsubscribe(string $listId, string $email): ?IDataObject;
    public function updateMemberDetails(string $listId, string $oldEmail, user\IClientDataObject $user): IDataObject;

    public function deleteMember(string $listId, string $email);
}





// DataObject
interface IDataObject extends spur\IDataObject
{
}
class DataObject extends spur\DataObject implements IDataObject
{
}


// List
interface IDataList extends spur\IDataList
{
}


// Filter
interface IFilter extends spur\IFilter
{
    public function getKeyName(): ?string;

    public function setOffset(int $offset);
    public function getOffset(): int;

    public function setFields(string ...$fields);
    public function addFields(string ...$fields);
    public function getFields(): array;

    public function setExcludeFields(string ...$fields);
    public function addExcludeFields(string ...$fields);
    public function getExcludeFields(): array;

    public function shouldIncludeLinks(bool $flag = null);
}



// Directional
interface IDirectionalFilter extends IFilter
{
    public function setSortField(?string $field);
    public function getSortField(): ?string;
    public function isReversed(bool $flag = null);
}




// Lists
interface IListFilter extends IDirectionalFilter
{
}

interface IInterestCategoryFilter extends IFilter
{
    public function setType(?string $type);
    public function getType(): ?string;
}

interface IInterestFilter extends IFilter
{
}



// Members
interface IMemberFilter extends IFilter
{
}
