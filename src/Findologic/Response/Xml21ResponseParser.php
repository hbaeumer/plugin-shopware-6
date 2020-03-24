<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Findologic\Response;

use FINDOLOGIC\Api\Responses\Xml21\Properties\LandingPage;
use FINDOLOGIC\Api\Responses\Xml21\Properties\Product;
use FINDOLOGIC\Api\Responses\Xml21\Properties\Promotion as ApiPromotion;
use FINDOLOGIC\Api\Responses\Xml21\Xml21Response;
use FINDOLOGIC\FinSearch\Findologic\Response\Xml21\Filter\Filter;
use FINDOLOGIC\FinSearch\Struct\CustomFilters;
use FINDOLOGIC\FinSearch\Struct\Pagination;
use FINDOLOGIC\FinSearch\Struct\Promotion;
use FINDOLOGIC\FinSearch\Struct\QueryInfoMessage;
use FINDOLOGIC\FinSearch\Struct\SmartDidYouMean;
use GuzzleHttp\Client;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Symfony\Component\HttpFoundation\Request;

class Xml21ResponseParser extends ResponseParser
{
    /**
     * @var Xml21Response
     */
    protected $response;

    public function getProductIds(): array
    {
        return array_map(
            static function (Product $product) {
                return $product->getId();
            },
            $this->response->getProducts()
        );
    }

    public function getSmartDidYouMeanExtension(Request $request): SmartDidYouMean
    {
        return new SmartDidYouMean($this->response->getQuery(), $request->getRequestUri());
    }

    public function getLandingPageUri(): ?string
    {
        $landingPage = $this->response->getLandingPage();
        if ($landingPage instanceof LandingPage) {
            return $landingPage->getLink();
        }

        return null;
    }

    public function getPromotionExtension(): ?Promotion
    {
        $promotion = $this->response->getPromotion();

        if ($promotion instanceof ApiPromotion) {
            return new Promotion($promotion->getImage(), $promotion->getLink());
        }

        return null;
    }

    public function getFilters(?Client $client = null): CustomFilters
    {
        $filters = array_merge($this->response->getMainFilters(), $this->response->getOtherFilters());

        $customFilters = new CustomFilters();
        foreach ($filters as $filter) {
            $customFilter = Filter::getInstance($filter);

            if ($customFilter && count($customFilter->getValues()) >= 1) {
                $customFilters->addFilter($customFilter);
            }
        }

        return $customFilters;
    }

    public function getPaginationExtension(?int $limit, ?int $offset): Pagination
    {
        return new Pagination($limit, $offset, $this->response->getResults()->getCount());
    }

    public function getQueryInfoMessage(ShopwareEvent $event): QueryInfoMessage
    {
        return new QueryInfoMessage($event, $this->response);
    }
}
