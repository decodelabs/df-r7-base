<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\fsa;

use df;
use df\core;
use df\spur;
use df\link;

interface IFhrsMediator extends spur\IGuzzleMediator
{
    public function fetchBasicRegions(): core\collection\ITree;
    public function pageBasicRegions(int $page, int $limit): core\collection\ITree;
    public function fetchRegions(): core\collection\ITree;
    public function pageRegions(int $page, int $limit): core\collection\ITree;
    public function fetchRegion(string $id): core\collection\ITree;

    public function fetchBasicAuthorities(): core\collection\ITree;
    public function pageBasicAuthorities(int $page, int $limit): core\collection\ITree;
    public function fetchAuthorities(): core\collection\ITree;
    public function pageAuthorities(int $page, int $limit): core\collection\ITree;
    public function fetchAuthority(string $id): core\collection\ITree;

    public function fetchBasicBusinessTypes(): core\collection\ITree;
    public function pageBasicBusinessTypes(int $page, int $limit): core\collection\ITree;
    public function fetchBusinessTypes(): core\collection\ITree;
    public function pageBusinessTypes(int $page, int $limit): core\collection\ITree;
    public function fetchBusinessType(string $id): core\collection\ITree;

    public function fetchBasicCountries(): core\collection\ITree;
    public function pageBasicCountries(int $page, int $limit): core\collection\ITree;
    public function fetchCountries(): core\collection\ITree;
    public function pageCountries(int $page, int $limit): core\collection\ITree;
    public function fetchCountry(string $id): core\collection\ITree;

    public function fetchSchemeTypes(): core\collection\ITree;
    public function fetchSortOptions(): core\collection\ITree;

    public function fetchScoreDescriptors(string $establishmentId): core\collection\ITree;
    public function fetchRatings(): core\collection\ITree;
    public function fetchRatingOperators(): core\collection\ITree;

    public function fetchBasicEstablishments(): core\collection\ITree;
    public function pageBasicEstablishments(int $page, int $limit): core\collection\ITree;
    public function fetchEstablishment(string $id): core\collection\ITree;
    public function searchEstablishments(array $keys, int $page=null, int $limit=null): core\collection\ITree;
}
