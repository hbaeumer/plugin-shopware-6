<?php

declare(strict_types=1);

namespace FINDOLOGIC\FinSearch\Export;

use FINDOLOGIC\FinSearch\Findologic\MainVariant;
use FINDOLOGIC\FinSearch\Struct\Config;
use FINDOLOGIC\FinSearch\Utils\Utils;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Grouping\FieldGrouping;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @deprecated ProductService class will be removed in plugin version 4.0
 */
class ProductService
{
    public const CONTAINER_ID = 'fin_search.product_service';

    /** @var Config */
    private $config;

    /** @var ContainerInterface */
    private $container;

    /** @var SalesChannelContext|null */
    private $salesChannelContext;

    public function __construct(
        ContainerInterface $container,
        ?SalesChannelContext $salesChannelContext = null,
        ?Config $config = null
    ) {
        $this->container = $container;
        $this->salesChannelContext = $salesChannelContext;
        $this->config = $config ?? $container->get(Config::class);
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    public static function getInstance(
        ContainerInterface $container,
        ?SalesChannelContext $salesChannelContext,
        ?Config $config = null
    ): ProductService {
        if ($container->has(self::CONTAINER_ID)) {
            $productService = $container->get(self::CONTAINER_ID);
        } else {
            $productService = new ProductService($container, $salesChannelContext, $config);
            $container->set(self::CONTAINER_ID, $productService);
        }

        if ($salesChannelContext && !$productService->getSalesChannelContext()) {
            $productService->setSalesChannelContext($salesChannelContext);
        }

        return $productService;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    public function setSalesChannelContext(SalesChannelContext $salesChannelContext): void
    {
        $this->salesChannelContext = $salesChannelContext;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    public function getSalesChannelContext(): ?SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    public function getConfig(): ?Config
    {
        return $this->config;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    public function getTotalProductCount(): int
    {
        $criteria = $this->buildProductCriteria();

        /** @var IdSearchResult $result */
        $result = $this->container->get('product.repository')->searchIds(
            $criteria,
            $this->salesChannelContext->getContext()
        );

        return $result->getTotal();
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    public function searchVisibleProducts(
        ?int $limit = null,
        ?int $offset = null,
        ?string $productId = null
    ): EntitySearchResult {
        $result = $this->getVisibleProducts($limit, $offset, $productId);
        $products = $this->buildProductsWithVariantInformation($result);

        return EntitySearchResult::createFrom($products);
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    public function searchAllProducts(
        ?int $limit = null,
        ?int $offset = null,
        ?string $productId = null
    ): EntitySearchResult {
        $criteria = $this->buildProductCriteria($limit, $offset);

        if ($productId) {
            $this->addProductIdFilters($criteria, $productId);
        }

        return $this->container->get('product.repository')->search(
            $criteria,
            $this->salesChannelContext->getContext()
        );
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    public function getAllCustomerGroups(): array
    {
        return $this->container->get('customer_group.repository')
            ->search(new Criteria(), $this->salesChannelContext->getContext())
            ->getElements();
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function addProductAssociations(Criteria $criteria): void
    {
        Utils::addProductAssociations($criteria);
        Utils::addChildrenAssociations($criteria);
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     *
     * If the given product is a parent product, returns all children of the product.
     * In case the given product already is a child, all siblings and the parent are returned. The siblings
     * do not include the given product itself.
     */
    protected function getChildrenOrSiblings(ProductEntity $product): ?ProductCollection
    {
        if (!$product->getParentId()) {
            return $product->getChildren();
        }

        $productRepository = $this->container->get('product.repository');
        $criteria = new Criteria([$product->getParentId()]);

        // Only get children of the same display group.
        $childrenCriteria = $criteria->getAssociation('children');
        $childrenCriteria->addFilter(
            new EqualsFilter('displayGroup', $product->getDisplayGroup())
        );
        $this->addVisibilityFilter($childrenCriteria);
        $this->handleAvailableStock($childrenCriteria);
        $this->addPriceZeroFilter($childrenCriteria);

        $this->addProductAssociations($criteria);

        /** @var ProductCollection $result */
        $result = $productRepository->search($criteria, $this->salesChannelContext->getContext());

        // Remove the given children, as the child product is considered as the product, which is shown
        // in the storefront. As we also want to get the data from the parent, we also manually add it here.
        $children = $result->first()->getChildren();
        $children->remove($product->getId());
        $children->add($result->first());

        return $children;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function getCriteriaWithProductVisibility(?int $limit = null, ?int $offset = null): Criteria
    {
        $criteria = $this->buildProductCriteria($limit, $offset);
        $this->addVisibilityFilter($criteria);
        $this->addPriceZeroFilter($criteria);

        return $criteria;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function buildProductCriteria(?int $limit = null, ?int $offset = null): Criteria
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('createdAt'));
        $criteria->addSorting(new FieldSorting('id'));

        $this->addGrouping($criteria);
        $this->handleAvailableStock($criteria);
        $this->addProductAssociations($criteria);

        if ($offset !== null) {
            $criteria->setOffset($offset);
        }
        if ($limit !== null) {
            $criteria->setLimit($limit);
        }

        return $criteria;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function addVisibilityFilter(Criteria $criteria): void
    {
        $criteria->addFilter(
            new ProductAvailableFilter(
                $this->salesChannelContext->getSalesChannel()->getId(),
                ProductVisibilityDefinition::VISIBILITY_SEARCH
            )
        );
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function addPriceZeroFilter(Criteria $criteria): void
    {
        $criteria->addFilter(
            new RangeFilter('price', [
                RangeFilter::GT => 0
            ])
        );
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     *
     * @see \Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader::addGrouping()
     */
    protected function addGrouping(Criteria $criteria): void
    {
        $criteria->addGroupField(new FieldGrouping('displayGroup'));

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [new EqualsFilter('displayGroup', null)]
            )
        );
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     *
     * @see \Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader::handleAvailableStock()
     */
    protected function handleAvailableStock(Criteria $criteria): void
    {
        $salesChannelId = $this->salesChannelContext->getSalesChannel()->getId();
        $systemConfigService = $this->container->get(SystemConfigService::class);

        $hide = $systemConfigService->get('core.listing.hideCloseoutProductsWhenOutOfStock', $salesChannelId);
        if (!$hide) {
            return;
        }

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [
                    new EqualsFilter('product.isCloseout', true),
                    new EqualsFilter('product.available', false),
                ]
            )
        );
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function addProductIdFilters(Criteria $criteria, string $productId): void
    {
        $productFilter = [
            new EqualsFilter('ean', $productId),
            new EqualsFilter('manufacturerNumber', $productId),
            new EqualsFilter('productNumber', $productId),
        ];

        // Only add the id filter in case the provided value is a valid uuid, to prevent Shopware
        // from throwing an exception in case it is not.
        if (Uuid::isValid($productId)) {
            $productFilter[] = new EqualsFilter('id', $productId);
        }

        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                $productFilter
            )
        );
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function getVisibleProducts(?int $limit, ?int $offset, ?string $productId): EntitySearchResult
    {
        $criteria = $this->getCriteriaWithProductVisibility($limit, $offset);

        if ($productId) {
            $this->addProductIdFilters($criteria, $productId);
        }

        return $this->container->get('product.repository')->search(
            $criteria,
            $this->salesChannelContext->getContext()
        );
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function buildProductsWithVariantInformation(EntitySearchResult $result): ProductCollection
    {
        $products = new ProductCollection();

        /** @var ProductEntity $product */
        foreach ($result->getEntities() as $product) {
            if ($product->getMainVariantId() !== null) {
                $mainProduct = $this->getRealMainProductWithVariants($product->getMainVariantId());
                if ($mainProduct) {
                    $product = $mainProduct;
                }
            }

            $this->assignChildrenOrSiblings($product);
            $products->add($product);
        }

        if ($this->config->getMainVariant() === MainVariant::SHOPWARE_DEFAULT) {
            return $products;
        }

        return $this->getProductsByMainVariantBasedOnConfig($products);
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function getProductsByMainVariantBasedOnConfig(ProductCollection $products): ProductCollection
    {
        $mainVariantConfig = $this->config->getMainVariant();
        $variantProducts = new ProductCollection();

        foreach ($products as $product) {
            switch ($mainVariantConfig) {
                case MainVariant::MAIN_PARENT:
                    $parent = $this->getParentByMainProduct($product);
                    break;
                case MainVariant::CHEAPEST:
                    $parent = $this->getParentByCheapestVariant($product);
                    break;
                default:
                    throw new InvalidArgumentException($mainVariantConfig);
            }

            $variantProducts->add($parent);
        }

        return $variantProducts;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function getRealMainProductWithVariants(string $realMainProductId): ?ProductEntity
    {
        return $this->getVisibleProducts(1, 0, $realMainProductId)->first();
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function assignChildrenOrSiblings(ProductEntity $product): void
    {
        $children = $this->getChildrenOrSiblings($product);
        $product->setChildren($children);
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function getParentByMainProduct(ProductEntity $product): ProductEntity
    {
        $parent = $product;
        $children = new ProductCollection();
        foreach ($product->getChildren() as $child) {
            if ($child->getParentId()) {
                $children->add($child);
            } else {
                $parent = $child;
            }
        }

        $parent->setChildren($children);

        return $parent;
    }

    /**
     * @deprecated ProductService class will be removed in plugin version 4.0
     */
    protected function getParentByCheapestVariant(ProductEntity $product): ProductEntity
    {
        $currencyId = $this->salesChannelContext->getSalesChannel()->getCurrencyId();
        $children = $product->getChildren();
        // Add the current product in the children collection, so we can include it when
        // checking for the cheapest price logic in the loop below.
        $children->add($product);
        // Get the real parent of the product. If no product is found, it means we
        // already have the real parent.
        $parent = $children->filter(static function (ProductEntity $childEntity) {
            return $childEntity->getParentId() === null;
        })->first();

        if (!$parent) {
            $parent = $product;
        }

        // Consider the current product to have the cheapest price by default, and look for
        // a cheaper product in its children.
        $cheapestPrice = $parent->getCurrencyPrice($currencyId);
        foreach ($children as $child) {
            if (!$price = $child->getCurrencyPrice($currencyId)) {
                continue;
            }

            if (
                $cheapestPrice->getGross() === 0.0 ||
                $price->getGross() < $cheapestPrice->getGross()
            ) {
                $cheapestPrice->setGross($price->getGross());
                $parent = $child;
            }
        }

        $configuredChildren = $children->filter(static function (ProductEntity $child) use ($parent) {
            return $child->getId() !== $parent->getId();
        });

        $parent->setParentId(null);
        $parent->setChildren($configuredChildren);

        return $parent;
    }
}
