<?php

namespace Klaviyo\Integration\Klaviyo\CatalogFeed;

use Klaviyo\Integration\Entity\Helper\ProductDataHelper;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;

class CatalogFeedProductItemTranslator
{
    private ProductDataHelper $productDataHelper;

    public function __construct(ProductDataHelper $productDataHelper)
    {
        $this->productDataHelper = $productDataHelper;
    }

    public function translateProducts(Context $context, ProductCollection $products): CatalogFeedProductItemCollection
    {
        $catalogFeedProductItemCollection = new CatalogFeedProductItemCollection();
        foreach ($products as $product) {
            $priceEntity = $product->getCurrencyPrice($context->getCurrencyId());
            $item = new CatalogFeedProductItemInfo(
                $product->getId(),
                $product->getName(),
                $this->productDataHelper->getProductViewPageUrl($product),
                $this->productDataHelper->getCoverImageUrl($context, $product),
                $product->getDescription(),
                $priceEntity->getGross(),
                $this->productDataHelper->getCategoryNames($context, $product)
            );

            $catalogFeedProductItemCollection->add($item);
        }

        return $catalogFeedProductItemCollection;
    }
}