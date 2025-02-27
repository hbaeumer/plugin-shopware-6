<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export\Adapters;

class AdapterFactory
{
    /** @var AttributeAdapter */
    private $attributeAdapter;

    /** @var BonusAdapter */
    private $bonusAdapter;

    /** @var DateAddedAdapter */
    private $dateAddedAdapter;

    /** @var DescriptionAdapter */
    private $descriptionAdapter;

    /** @var DefaultPropertiesAdapter */
    private $defaultPropertiesAdapter;

    /** @var ImagesAdapter */
    private $imagesAdapter;

    /** @var KeywordsAdapter */
    private $keywordsAdapter;

    /** @var NameAdapter */
    private $nameAdapter;

    /** @var OrderNumberAdapter */
    private $orderNumberAdapter;

    /** @var PriceAdapter */
    private $priceAdapter;

    /** @var SalesFrequencyAdapter */
    private $salesFrequencyAdapter;

    /** @var SortAdapter */
    private $sortAdapter;

    /** @var SummaryAdapter */
    private $summaryAdapter;

    /** @var ShopwarePropertiesAdapter */
    private $shopwarePropertiesAdapter;

    /** @var UrlAdapter */
    private $urlAdapter;

    /** @var UserGroupsAdapter */
    private $userGroupsAdapter;

    public function __construct(
        AttributeAdapter $attributeAdapter,
        BonusAdapter $bonusAdapter,
        DateAddedAdapter $dateAddedAdapter,
        DescriptionAdapter $descriptionAdapter,
        DefaultPropertiesAdapter $defaultPropertiesAdapter,
        ImagesAdapter $imagesAdapter,
        KeywordsAdapter $keywordsAdapter,
        NameAdapter $itemNameAdapter,
        OrderNumberAdapter $orderNumberAdapter,
        PriceAdapter $priceAdapter,
        SalesFrequencyAdapter $salesFrequencyAdapter,
        SortAdapter $sortAdapter,
        SummaryAdapter $summaryAdapter,
        ShopwarePropertiesAdapter $shopwarePropertiesAdapter,
        UrlAdapter $urlAdapter,
        UserGroupsAdapter $userGroupsAdapter
    ) {
        $this->attributeAdapter = $attributeAdapter;
        $this->bonusAdapter = $bonusAdapter;
        $this->dateAddedAdapter = $dateAddedAdapter;
        $this->descriptionAdapter = $descriptionAdapter;
        $this->defaultPropertiesAdapter = $defaultPropertiesAdapter;
        $this->imagesAdapter = $imagesAdapter;
        $this->keywordsAdapter = $keywordsAdapter;
        $this->nameAdapter = $itemNameAdapter;
        $this->orderNumberAdapter = $orderNumberAdapter;
        $this->priceAdapter = $priceAdapter;
        $this->salesFrequencyAdapter = $salesFrequencyAdapter;
        $this->sortAdapter = $sortAdapter;
        $this->summaryAdapter = $summaryAdapter;
        $this->shopwarePropertiesAdapter = $shopwarePropertiesAdapter;
        $this->urlAdapter = $urlAdapter;
        $this->userGroupsAdapter = $userGroupsAdapter;
    }

    public function getAttributeAdapter(): AttributeAdapter
    {
        return $this->attributeAdapter;
    }

    public function getBonusAdapter(): BonusAdapter
    {
        return $this->bonusAdapter;
    }

    public function getDateAddedAdapter(): DateAddedAdapter
    {
        return $this->dateAddedAdapter;
    }

    public function getDescriptionAdapter(): DescriptionAdapter
    {
        return $this->descriptionAdapter;
    }

    public function getDefaultPropertiesAdapter(): DefaultPropertiesAdapter
    {
        return $this->defaultPropertiesAdapter;
    }

    public function getImagesAdapter(): ImagesAdapter
    {
        return $this->imagesAdapter;
    }

    public function getKeywordsAdapter(): KeywordsAdapter
    {
        return $this->keywordsAdapter;
    }

    public function getNameAdapter(): NameAdapter
    {
        return $this->nameAdapter;
    }

    public function getOrderNumbersAdapter(): OrderNumberAdapter
    {
        return $this->orderNumberAdapter;
    }

    public function getPriceAdapter(): PriceAdapter
    {
        return $this->priceAdapter;
    }

    public function getSalesFrequencyAdapter(): SalesFrequencyAdapter
    {
        return $this->salesFrequencyAdapter;
    }

    public function getSortAdapter(): SortAdapter
    {
        return $this->sortAdapter;
    }

    public function getSummaryAdapter(): SummaryAdapter
    {
        return $this->summaryAdapter;
    }

    public function getShopwarePropertiesAdapter(): ShopwarePropertiesAdapter
    {
        return $this->shopwarePropertiesAdapter;
    }

    public function getUrlAdapter(): UrlAdapter
    {
        return $this->urlAdapter;
    }

    public function getUserGroupsAdapter(): UserGroupsAdapter
    {
        return $this->userGroupsAdapter;
    }
}
