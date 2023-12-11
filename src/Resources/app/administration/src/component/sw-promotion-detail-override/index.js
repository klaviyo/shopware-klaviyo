import template from './sw-promotion-v2-detail.html.twig';

Shopware.Component.override('sw-promotion-v2-detail', {
    template,

    methods: {
        sendPromotionToExport() {
            if (!this.promotionId) {
                this.createNotificationError({
                    message: this.$tc('klaviyo_integration_plugin.promotion.notification.notificationExportErrorMessage', 0, {
                        entityName: this.promotion.name,
                    }),
                });

                return;
            }

            const basePath = Shopware.Context.api.apiPath;

            window.location.href = basePath + '/klaviyo/integration/promotion/export?id=' + this.promotionId;
        }
    }
});