import template from './klaviyo-integration-settings-general.html.twig';

const {Component} = Shopware;

Component.register('klaviyo-integration-settings-general', {
    template,

    props: {
        actualConfigData: {
            type: Object,
            required: true,
        },
        allConfigs: {
            type: Object,
            required: true,
        },
        selectedSalesChannelId: {
            type: String,
            required: false,
            default: null,
        }
    },

    data() {
        return {
            isLoading: false,
        };
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            const configPrefix = 'KlaviyoIntegrationPlugin.config.',
                defaultConfigs = {
                    catalogFeedProductsCount: 25000,
                    trackViewedProduct: true,
                    trackRecentlyViewedItems: true,
                    trackAddedToCart: true,
                    trackStartedCheckout: true,
                    trackPlacedOrder: true,
                    trackOrderedProduct: true,
                    trackFulfilledOrder: true,
                    trackCancelledOrder: true,
                    trackRefundedOrder: true,
                    isInitializeKlaviyoAfterInteraction: false
                };

            /**
             * Initialize config data with default values.
             */
            for (const [key, defaultValue] of Object.entries(defaultConfigs)) {
                if (this.allConfigs['null'][configPrefix + key] === undefined) {
                    this.$set(this.allConfigs['null'], configPrefix + key, defaultValue);
                }
            }
        },

        checkTextFieldInheritance(value) {
            if (typeof value !== 'string') {
                return true;
            }

            return value.length <= 0;
        },

        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        }
    },
});
