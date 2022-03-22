<?php

namespace Klaviyo\Integration\Klaviyo\FrontendApi\Translator;

use Klaviyo\Integration\Entity\Helper\ProductDataHelper;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\ProductInfo;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Seo\SeoUrlPlaceholderHandlerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductTranslator
{
    private ProductDataHelper $productDataHelper;
    private SeoUrlPlaceholderHandlerInterface $seoUrlReplacer;

    public function __construct(
        ProductDataHelper $productDataHelper,
        SeoUrlPlaceholderHandlerInterface $seoUrlReplacer
    ) {
        $this->productDataHelper = $productDataHelper;
        $this->seoUrlReplacer = $seoUrlReplacer;
    }

    public function translateToProductInfo(
        Context $context,
        SalesChannelContext $salesChannelContext,
        ProductEntity $product
    ): ProductInfo {
        $productViewPageUrl = $this->getProductUrl($product, $salesChannelContext);
        $imageUrl = $this->productDataHelper->getCoverImageUrl($context, $product);
        $categories = $this->productDataHelper->getCategoryNames($context, $product);
        $manufacturerName = $this->productDataHelper->getManufacturerName($context, $product);

        $priceEntity = $product->getCurrencyPrice($context->getCurrencyId());
        $grossPrice = $priceEntity ? $priceEntity->getGross() : null;
        $compareAtPrice = null;

        if ($grossPrice && $priceEntity->getListPrice() && $priceEntity->getListPrice()->getGross() > $grossPrice) {
            $compareAtPrice = $priceEntity->getListPrice()->getGross();
        }

        $parentProduct = null;
        if ($product->getParentId()) {
            $parentProduct = $this->productDataHelper->getProductById($context, $product->getParentId());
        }

        return new ProductInfo(
            $parentProduct ? $parentProduct->getName() : $product->getName(),
            $product->getId(),
            $product->getProductNumber(),
            $categories,
            $imageUrl,
            $productViewPageUrl,
            $manufacturerName,
            $grossPrice,
            $compareAtPrice
        );
    }

    private function getProductUrl(ProductEntity $product, SalesChannelContext $context): string
    {
        if ($domains = $context->getSalesChannel()->getDomains()) {
            $raw = $this->seoUrlReplacer->generate('frontend.detail.page', ['productId' => $product->getId()]);

            return $this->seoUrlReplacer->replace($raw, $domains->first()->getUrl(), $context);
        }

        return $this->productDataHelper->getProductViewPageUrl($product);
    }
}