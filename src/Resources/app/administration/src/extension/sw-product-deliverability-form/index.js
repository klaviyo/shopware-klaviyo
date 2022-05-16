import template from './sw-product-deliverability-form.html.twig';

const { Component } = Shopware;

Component.override('sw-product-deliverability-form', {
    template,

    methods: {
        createdComponent() {
            if (typeof this.product.stock === 'undefined') {
                this.product.stock = 0;
            }

            if (this.product) {
                if (!this.product.customFields) {
                    this.product.customFields = {};
                }

                if (!this.product.customFields.klaviyo_back_in_stock_disabled) {
                    this.product.customFields.klaviyo_back_in_stock_disabled = false;
                }
            }
        }
    }
});
