<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\FrontendApi\Translator;

use Klaviyo\Integration\Entity\Helper\ProductDataHelper;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\ProductInfo;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductTranslator
{
    private ProductDataHelper $productDataHelper;

    public function __construct(ProductDataHelper $productDataHelper)
    {
        $this->productDataHelper = $productDataHelper;
    }

    public function translateToProductInfo(
        Context $context,
        SalesChannelContext $salesChannelContext,
        ProductEntity $product
    ): ProductInfo {
        $productViewPageUrl = $this->productDataHelper->getProductViewPageUrl($product, $salesChannelContext);
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
}