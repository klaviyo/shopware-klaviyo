import template from './sw-promotion-v2-list.html.twig';

Shopware.Component.override('sw-promotion-v2-list', {
    template,

    methods: {
        getExportUrl() {
            const basePath = Shopware.Context.api.apiPath;

            return basePath + '/klaviyo/integration/promotion/export';
        },

        sendPromotionToExport(promotionId) {
            if (!promotionId) {
                return;
            }
            window.location.href = this.getExportUrl() + '?id=' + promotionId;
        }
    }
});