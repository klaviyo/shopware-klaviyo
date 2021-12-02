<?php

namespace Klaviyo\Integration\Entity\Helper;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductDataHelper
{
    private UrlGeneratorInterface $urlGenerator;
    private EntityRepositoryInterface $productRepository;
    private EntityRepositoryInterface $productMediaRepository;
    private EntityRepositoryInterface $categoriesRepository;
    private EntityRepositoryInterface $productManufacturerRepository;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $productMediaRepository,
        EntityRepositoryInterface $categoriesRepository,
        EntityRepositoryInterface $productManufacturerRepository
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->productRepository = $productRepository;
        $this->productMediaRepository = $productMediaRepository;
        $this->categoriesRepository = $categoriesRepository;
        $this->productManufacturerRepository = $productManufacturerRepository;
    }

    public function getProductViewPageUrl(ProductEntity $productEntity): string
    {
        $productUrl = $this->urlGenerator
            ->generate(
                'frontend.detail.page',
                ['productId' => $productEntity->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

        return $productUrl;
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

    public function getManufacturerName(Context $context, ProductEntity $productEntity): ?string
    {
        $manufacturer = $this->getProductManufacturer($context, $productEntity);

        return $manufacturer ? $manufacturer->getName() : null;
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
}