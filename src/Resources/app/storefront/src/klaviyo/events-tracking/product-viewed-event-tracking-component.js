import Plugin from 'src/plugin-system/plugin.class';

export default class KlaviyoProductViewedEventTrackingComponent extends Plugin {
    static options = {
        productInfo: null
    }

    init() {
        window._learnq = window._learnq || [];

        if (!this.options.productInfo && console) {
            console.error('Product info configuration was not set');
            return;
        }

        window._learnq.push(["track", "Viewed Product", this.options.productInfo]);
        window._learnq.push(
            [
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
