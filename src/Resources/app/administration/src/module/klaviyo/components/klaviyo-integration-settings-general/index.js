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
            configPath: 'KlaviyoIntegrationPlugin.config',
            isLoading: false,
            systemLanguages: [],
        };
    },

    methods: {

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
