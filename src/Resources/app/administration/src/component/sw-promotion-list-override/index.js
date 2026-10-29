import template from './sw-promotion-v2-list.html.twig';

Shopware.Component.override('sw-promotion-v2-list', {
    template,

    inject: ['klaviyoPromotionExportApiService'],

    // the core sw-promotion-v2-list has no notification mixin on Shopware 6.5 (added in 6.6)
    mixins: [Shopware.Mixin.getByName('notification')],

    methods: {
        sendPromotionToExport(promotionId) {
            if (!promotionId) {
                return;
            }

            this.downloadPromotionExport(promotionId);
        },

        sendGlobalPromotionToExport() {
            this.downloadPromotionExport();
        },

        downloadPromotionExport(promotionId = null) {
            return this.klaviyoPromotionExportApiService
                .downloadCsv(promotionId)
                .catch(() => {
                    this.createNotificationError({
                        message: this.$tc('klaviyo_integration_plugin.promotion.notification.notificationExportFailedMessage'),
                    });
                });
        },
    },
});
