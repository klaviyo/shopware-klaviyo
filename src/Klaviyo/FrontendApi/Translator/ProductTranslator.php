<?php declare(strict_types=1);

namespace Klaviyo\Integration\Klaviyo\FrontendApi\Translator;

use Klaviyo\Integration\Configuration\ConfigurationRegistry;
use Klaviyo\Integration\Entity\Helper\ProductDataHelper;
use Klaviyo\Integration\Klaviyo\FrontendApi\DTO\ProductInfo;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductTranslator
{
    private ProductDataHelper $productDataHelper;
    private ConfigurationRegistry $configurationRegistry;

    public function __construct(
        ProductDataHelper $productDataHelper,
        ConfigurationRegistry $configurationRegistry,
    ) {
        $this->productDataHelper = $productDataHelper;
        $this->configurationRegistry = $configurationRegistry;
    }

    public function translateToProductInfo(
        Context $context,
        SalesChannelContext $salesChannelContext,
        ProductEntity $product
    ): ProductInfo {
        $productViewPageUrl = $this->productDataHelper->getProductViewPageUrlByContext($product, $salesChannelContext);
        $imageUrl = $this->productDataHelper->getCoverImageUrl($context, $product);
        $categories = $this->productDataHelper->getCategoryNames($context, $product);
        $manufacturerName = $this->productDataHelper->getManufacturerName($context, $product);

        $priceEntity = $product->getCurrencyPrice($context->getCurrencyId());
        $grossPrice = $priceEntity ? $priceEntity->getGross() : null;
        $compareAtPrice = null;

        if ($grossPrice && $priceEntity->getListPrice() && $priceEntity->getListPrice()->getGross() > $grossPrice) {
            $compareAtPrice = $priceEntity->getListPrice()->getGross();
        }

        $name = $product->getName();
        if ($product->getParentId()) {
            $parentProduct = $this->productDataHelper->getProductById($context, $product->getParentId());
            $name = $parentProduct->getName();
        }

        $productIdType = $this->configurationRegistry->getConfiguration(
            $salesChannelContext->getSalesChannelId()
        )->getBisVariantField();

        if ($name == null) {
            if ($productIdType === 'product-number') {
                if (isset($parentProduct)) {
                    $prodId = $parentProduct->getProductNumber();
                } else {
                    $prodId = $product->getProductNumber();
                }
            } else {
                if (isset($parentProduct)) {
                    $prodId = $parentProduct->getId();
                } else {
                    $prodId = $product->getId();
                }
            }

            $name = $this->productDataHelper->getProductNameById($prodId);
        }

        return new ProductInfo(
            $name,
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