import Plugin from 'src/plugin-system/plugin.class';
import KlaviyoGateway from "../util/gateway";

export default class KlaviyoProductViewedEventTrackingComponent extends Plugin {
    static options = {
        productInfo: null
    }

    init() {
        if (!this.options.productInfo && console) {
            console.error('[Klaviyo] Product info configuration was not set.');
            return;
        }

        KlaviyoGateway.push(["track", "Viewed Product", this.options.productInfo]);
        KlaviyoGateway.push([
            "trackViewedItem",
            {
                "Title": this.options.productInfo.ProductName,
                "ItemId": this.options.productInfo.ProductID,
                "Categories": this.options.productInfo.Categories,
                "ImageUrl": this.options.productInfo.ImageURL,
                "Url": this.options.productInfo.URL,
                "Metadata": {
                    "Brand": this.options.productInfo.Brand,
                    "Price": this.options.productInfo.Price,
                    "CompareAtPrice": this.options.productInfo.CompareAtPrice
                }
            }
        ]);
    }
}
