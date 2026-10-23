import template from './sw-promotion-v2-detail.html.twig';

Shopware.Component.override('sw-promotion-v2-detail', {
    template,

    inject: ['klaviyoPromotionExportApiService'],

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

            return this.klaviyoPromotionExportApiService
                .downloadCsv(this.promotionId)
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('klaviyo_integration_plugin.promotion.notification.notificationExportFailedMessage'),
                    });
                });
        },
    },
});
