import template from './sw-product-deliverability-form.html.twig';

const { Component } = Shopware;

Component.override('sw-product-deliverability-form', {
    template,

    methods: {
        createdComponent() {
            this.$super('createdComponent');

            if (!this.product) {
                return;
            }

            if (!this.product.customFields) {
                this.product.customFields = {};
            }

            if (this.parentProduct && !this.parentProduct.customFields) {
                this.parentProduct.customFields = {};
            }

            if (this.parentProduct && !this.parentProduct.customFields) {
                this.parentProduct.customFields = {};
            }

        }
    }
});
