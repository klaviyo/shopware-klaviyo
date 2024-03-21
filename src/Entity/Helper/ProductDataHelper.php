<?php

declare(strict_types=1);

namespace Klaviyo\Integration\Entity\Helper;

use Klaviyo\Integration\Klaviyo\Client\Exception\OrderItemProductNotFound;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductDataHelper
{
    private UrlGeneratorInterface $urlGenerator;
    private EntityRepositoryInterface $productRepository;
    private EntityRepositoryInterface $productMediaRepository;
    private EntityRepositoryInterface $categoriesRepository;
    private EntityRepositoryInterface $productManufacturerRepository;
    private SeoUrlPlaceholderHandlerInterface $seoUrlReplacer;
    private EntityRepositoryInterface $salesChannelRepository;
    private AbstractSalesChannelContextFactory $salesChannelContextFactory;
    private RequestStack $requestStack;
    private array $contexts = [];

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $productMediaRepository,
        EntityRepositoryInterface $categoriesRepository,
        EntityRepositoryInterface $productManufacturerRepository,
        SeoUrlPlaceholderHandlerInterface $seoUrlReplacer,
        EntityRepositoryInterface $salesChannelRepository,
        AbstractSalesChannelContextFactory $salesChannelContextFactory,
        RequestStack $requestStack
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->productRepository = $productRepository;
        $this->productMediaRepository = $productMediaRepository;
        $this->categoriesRepository = $categoriesRepository;
        $this->productManufacturerRepository = $productManufacturerRepository;
        $this->seoUrlReplacer = $seoUrlReplacer;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->requestStack = $requestStack;
    }

    public function getProductViewPageUrlByContext(
        ProductEntity $productEntity,
        SalesChannelContext $salesChannelContext
    ): string {
        $request = $this->requestStack->getCurrentRequest();
        $raw = $this->seoUrlReplacer->generate('frontend.detail.page', ['productId' => $productEntity->getId()]);

        if (null !== $request && !empty($request->get(RequestTransformer::STOREFRONT_URL))) {
            return $this->seoUrlReplacer->replace(
                $raw,
                $request->get(RequestTransformer::STOREFRONT_URL),
                $salesChannelContext
            );
        }

        foreach ($salesChannelContext->getSalesChannel()->getDomains() as $domain) {
            if ($domain->getLanguageId() == $salesChannelContext->getLanguageId()) {
                return $this->seoUrlReplacer->replace($raw, $domain->getUrl(), $salesChannelContext);
            }
        }

        if ($salesChannelContext->getSalesChannel() && $salesChannelContext->getSalesChannel()->getDomains()) {
            return $this->seoUrlReplacer->replace(
                $raw,
                $salesChannelContext->getSalesChannel()->getDomains()->first()->getUrl(),
                $salesChannelContext
            );
        }

        return $this->urlGenerator
            ->generate(
                'frontend.detail.page',
                ['productId' => $productEntity->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
    }

    public function getProductViewPageUrlByChannelId(
        ProductEntity $productEntity,
        string $channelId,
        Context $context,
        $languageId
    ): string {
        $salesChannelContext = $this->getSalesChannelContext($channelId, $context, $languageId);

        return $this->getProductViewPageUrlByContext($productEntity, $salesChannelContext);
    }

    /**
     * @throws OrderItemProductNotFound
     */
    public function getLineItemProduct(Context $context, OrderLineItemEntity $orderLineItemEntity): ProductEntity
    {
        if ($orderLineItemEntity->getProduct()) {
            return $orderLineItemEntity->getProduct();
        }

        if (!$orderLineItemEntity->getProductId()) {
            throw new OrderItemProductNotFound('Order line item product id is not defined');
        }

        $productEntity = $this->productRepository
            ->search(new Criteria([$orderLineItemEntity->getProductId()]), $context)
            ->first();

        if (!$productEntity) {
            throw new OrderItemProductNotFound(
                \sprintf('Product[id: %s] was not found', $orderLineItemEntity->getProductId())
            );
        }

        return $productEntity;
    }

    public function getCoverImageUrl(Context $context, ProductEntity $productEntity): string
    {
        $coverId = $productEntity->getCoverId();
        if (!$coverId) {
            $parentProduct = $this->getProductParent($context, $productEntity);
            if ($parentProduct) {
                $coverId = $parentProduct->getCoverId();
            }

            if (!$coverId) {
                return '';
            }
        }

        $criteria = new Criteria([$coverId]);
        $criteria->addAssociation('media');

        /** @var ProductMediaEntity $mediaEntitiesResult */
        $mediaEntitiesResult = $this->productMediaRepository
            ->search($criteria, $context)->first();

        return $mediaEntitiesResult ? $mediaEntitiesResult->getMedia()->getUrl() : '';
    }

    private function getProductParent(Context $context, ProductEntity $productEntity): ?ProductEntity
    {
        if ($productEntity->getParent()) {
            return $productEntity->getParent();
        }

        if (!$productEntity->getParentId()) {
            return null;
        }

        $parent = $this->getProductById($context, $productEntity->getParentId());
        $productEntity->setParent($parent);

        return $parent;
    }

    /**
     * @param Context $context
     * @param ProductEntity $productEntity
     *
     * @return array|string[]
     */
    public function getCategoryNames(Context $context, ProductEntity $productEntity): array
    {
        $categoriesEntities = $this->getProductCategories($context, $productEntity);

        $categories = [];

        $categoriesWithDynamicProductGroups = $this->getCategoriesWithDynamicProductGroups($context);
        $streamIds = $productEntity->getStreamIds();

        if ($categoriesWithDynamicProductGroups->count() > 0 && $streamIds) {
            foreach ($categoriesWithDynamicProductGroups as $category) {
                if (!$category->getProductStreamId()) {
                    continue;
                }

                foreach ($streamIds as $streamId) {
                    if ($streamId === $category->getProductStreamId()) {
                        $categories[] = $category->getName();
                    }
                }
            }
        }

        foreach ($categoriesEntities as $categoryEntity) {
            $categories[] = $categoryEntity->getName();
        }

        return $categories;
    }

    private function getProductCategories(Context $context, ProductEntity $productEntity): CategoryCollection
    {
        if ($productEntity->getCategories()) {
            return $productEntity->getCategories();
        }

        $categoriesIds = $productEntity->getCategoryIds();
        if (empty($categoriesIds)) {
            return new CategoryCollection();
        }

        /** @var CategoryCollection $categoriesCollection */
        $categoriesCollection = $this->categoriesRepository
            ->search(new Criteria($categoriesIds), $context)
            ->getEntities();

        return $categoriesCollection;
    }

    private function getCategoriesWithDynamicProductGroups(Context $context): CategoryCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter(
                'productAssignmentType',
                CategoryDefinition::PRODUCT_ASSIGNMENT_TYPE_PRODUCT_STREAM
            )
        );

        return $this->categoriesRepository->search($criteria, $context)->getEntities();
    }

    public function getManufacturerName(Context $context, ProductEntity $productEntity): ?string
    {
        $manufacturer = $this->getProductManufacturer($context, $productEntity);

        return $manufacturer ? $manufacturer->getTranslation('name') : null;
    }

    private function getProductManufacturer(Context $context, ProductEntity $productEntity): ?ProductManufacturerEntity
    {
        if ($productEntity->getManufacturer()) {
            return $productEntity->getManufacturer();
        }

        $manufacturerId = $productEntity->getManufacturerId();

        if (!$manufacturerId) {
            $productParent = $this->getProductParent($context, $productEntity);

            if ($productParent) {
                $manufacturerId = $productParent->getManufacturerId();
            }

            if (!$manufacturerId) {
                return null;
            }
        }

        return $this->productManufacturerRepository->search(
            new Criteria([$manufacturerId]),
            $context
        )->first();
    }

    public function getProductById(Context $context, string $productId): ProductEntity
    {
        return $this->productRepository->search(new Criteria([$productId]), $context)->first();
    }

    public function getSalesChannelContext(string $channelId, Context $context, $languageId = null): SalesChannelContext
    {
        if (isset($this->contexts[$this->getHashedIdentificator($channelId, $languageId)])) {
            return $this->contexts[$this->getHashedIdentificator($channelId, $languageId)];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $channelId));
        $criteria->addAssociation('domains');
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if (!$languageId) {
            $salesChannelContext = $this->salesChannelContextFactory->create(
                Uuid::randomHex(),
                $salesChannel->getId()
            );
        } else {
            $salesChannelContext = $this->salesChannelContextFactory->create(
                Uuid::randomHex(),
                $salesChannel->getId(),
                [SalesChannelContextService::LANGUAGE_ID => $languageId]
            );
        }

        return $this->contexts[$this->getHashedIdentificator($channelId, $languageId)] = $salesChannelContext;
    }

    /**
     * @param null|string $channelId
     * @param null|string $languageId
     * @return string
     */
    private function getHashedIdentificator(?string $channelId, ?string $languageId): string
    {
        return $channelId . '-' . $languageId;
    }

    public function getProductNameById(string $productId)
    {
        $context = Context::createDefaultContext();

        $context = new Context(
            new SystemSource(),
            [],
            $context->getCurrencyId(),
            [$context->getLanguageId(), Defaults::LANGUAGE_SYSTEM]
        );

        $searchResult = $this->productRepository->search(new Criteria([$productId]), $context)->first();
        if ($searchResult) {
            return $searchResult->getName();
        }

        return null;
    }
}
