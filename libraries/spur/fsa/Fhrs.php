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
use df\flex;

use DecodeLabs\Exceptional;
use Psr\Http\Message\ResponseInterface;

class Fhrs implements IFhrsMediator
{
    use spur\TGuzzleMediator;

    const API_URL = 'http://api.ratings.food.gov.uk/';
    const VERSION = 2;


    // Regions
    public function fetchBasicRegions(): core\collection\ITree
    {
        return $this->requestJson('get', 'Regions/basic')->regions;
    }

    public function pageBasicRegions(int $page, int $limit): core\collection\ITree
    {
        return $this->requestJson('get', 'Regions/basic/'.$page.'/'.$limit);
    }

    public function fetchRegions(): core\collection\ITree
    {
        return $this->requestJson('get', 'Regions')->regions;
    }

    public function pageRegions(int $page, int $limit): core\collection\ITree
    {
        return $this->requestJson('get', 'Regions/'.$page.'/'.$limit);
    }

    public function fetchRegion(string $id): core\collection\ITree
    {
        return $this->requestJson('get', 'Regions/'.$id);
    }


    // Authorities
    public function fetchBasicAuthorities(): core\collection\ITree
    {
        return $this->requestJson('get', 'Authorities/basic')->authorities;
    }

    public function pageBasicAuthorities(int $page, int $limit): core\collection\ITree
    {
        return $this->requestJson('get', 'Authorities/basic/'.$page.'/'.$limit);
    }

    public function fetchAuthorities(): core\collection\ITree
    {
        return $this->requestJson('get', 'Authorities')->authorities;
    }

    public function pageAuthorities(int $page, int $limit): core\collection\ITree
    {
        return $this->requestJson('get', 'Authorities/'.$page.'/'.$limit);
    }

    public function fetchAuthority(string $id): core\collection\ITree
    {
        return $this->requestJson('get', 'Authorities/'.$id);
    }


    // Business types
    public function fetchBasicBusinessTypes(): core\collection\ITree
    {
        return $this->requestJson('get', 'BusinessTypes/basic')->businessTypes;
    }

    public function pageBasicBusinessTypes(int $page, int $limit): core\collection\ITree
    {
        return $this->requestJson('get', 'BusinessTypes/basic/'.$page.'/'.$limit);
    }

    public function fetchBusinessTypes(): core\collection\ITree
    {
        return $this->requestJson('get', 'BusinessTypes')->businessTypes;
    }

    public function pageBusinessTypes(int $page, int $limit): core\collection\ITree
    {
        return $this->requestJson('get', 'BusinessTypes/'.$page.'/'.$limit);
    }

    public function fetchBusinessType(string $id): core\collection\ITree
    {
        return $this->requestJson('get', 'BusinessTypes/'.$id);
    }


    // Countries
    public function fetchBasicCountries(): core\collection\ITree
    {
        return $this->requestJson('get', 'Countries/basic')->countries;
    }

    public function pageBasicCountries(int $page, int $limit): core\collection\ITree
    {
        return $this->requestJson('get', 'Countries/basic/'.$page.'/'.$limit);
    }

    public function fetchCountries(): core\collection\ITree
    {
        return $this->requestJson('get', 'Countries')->countries;
    }

    public function pageCountries(int $page, int $limit): core\collection\ITree
    {
        return $this->requestJson('get', 'Countries/'.$page.'/'.$limit);
    }

    public function fetchCountry(string $id): core\collection\ITree
    {
        return $this->requestJson('get', 'Countries/'.$id);
    }


    // Scheme types
    public function fetchSchemeTypes(): core\collection\ITree
    {
        return $this->requestJson('get', 'SchemeTypes')->schemeTypes;
    }


    // Sort options
    public function fetchSortOptions(): core\collection\ITree
    {
        return $this->requestJson('get', 'SortOptions')->sortOptions;
    }


    // Ratings
    public function fetchScoreDescriptors(string $establishmentId): core\collection\ITree
    {
        return $this->requestJson('get', 'ScoreDescriptors', [
            'establishmentId' => $establishmentId
        ])->scoreDescriptors;
    }

    public function fetchRatings(): core\collection\ITree
    {
        return $this->requestJson('get', 'Ratings')->ratings;
    }

    public function fetchRatingOperators(): core\collection\ITree
    {
        return $this->requestJson('get', 'RatingOperators')->ratingOperators;
    }


    // Establishments
    public function fetchBasicEstablishments(): core\collection\ITree
    {
        return $this->requestJson('get', 'Establishments/basic')->establishments;
    }

    public function pageBasicEstablishments(int $page, int $limit): core\collection\ITree
    {
        return $this->requestJson('get', 'Establishments/basic/'.$page.'/'.$limit);
    }

    public function fetchEstablishment(string $id): core\collection\ITree
    {
        return $this->requestJson('get', 'Establishments/'.$id);
    }

    public function searchEstablishments(array $keys, int $page=null, int $limit=null): core\collection\ITree
    {
        foreach ($keys as $key => $value) {
            switch ($key) {
                case 'name':
                case 'address':
                case 'longitude':
                case 'latitude':
                case 'maxDistanceLimit':
                case 'businessTypeId':
                case 'schemeTypeKey':
                case 'ratingKey':
                case 'ratingOperatorKey':
                case 'localAuthorityId':
                case 'countryId':
                case 'sortOptionKey':
                    break;

                default:
                    throw Exceptional::InvalidArgument(
                        'Invalid establishment search key: '.$key
                    );
            }
        }

        if ($page !== null) {
            $keys['pageNumber'] = $page;
        }

        if ($limit !== null) {
            $keys['pageSize'] = $limit;
        }

        return $this->requestJson('get', 'Establishments', $keys);
    }



    ### IO
    public function createUrl(string $path): link\http\IUrl
    {
        $base = self::API_URL;
        return link\http\Url::factory($base.ltrim($path, '/'));
    }

    protected function _prepareRequest(link\http\IRequest $request): link\http\IRequest
    {
        $request->getHeaders()
            ->set('X-Api-Version', self::VERSION)
            ->set('Accept-Language', 'en-GB')
            ->set('Accept', 'application/json');

        return $request;
    }

    protected function _extractResponseError(ResponseInterface $response)
    {
        $data = flex\Json::stringToTree((string)$response->getBody());
        $message = $data->get('Message', $data->getValue() ?? 'Request failed');
        $code = $response->getStatusCode();
        $errorType = 'Api';

        switch ($code) {
            case 404:
                $errorType .= ',NotFound';
                break;
        }

        return Exceptional::{$errorType}([
            'message' => $message,
            'code' => $code
        ]);
    }
}
