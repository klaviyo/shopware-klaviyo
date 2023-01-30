import template from './klaviyo-integration-settings-general.html.twig';
import './klaviyo-integration-settings-general.scss';

const {Component, Mixin, Utils} = Shopware;

Component.register('klaviyo-integration-settings-general', {
    template,

    inject: ['klaviyoApiKeyValidatorService'],
    mixins: [Mixin.getByName('notification')],

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
        },
        privateKeyErrorState: {
            type: Object,
            required: false,
            default: null,
        },
        publicKeyErrorState: {
            type: Object,
            required: false,
            default: null,
        },
        listNameErrorState: {
            type: Object,
            required: false,
            default: null,
        },
    },

    data() {
        return {
            isLoading: false,
            apiValidationInProgress: false
        };
    },

    computed : {
        createBisVariantFieldOptions() {
            return [
                {
                    label: this.$tc('klaviyo-integration-settings.configs.bisVariantField.productId'),
                    value: 'product-id'
                },
                {
                    label: this.$tc('sw-product.basicForm.labelProductNumber'),
                    value: 'product-number'
                }
            ]
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            const configPrefix = 'KlaviyoIntegrationPlugin.config.',
                defaultConfigs = {
                    enabled: false,
                    bisVariantField: 'product-number',
                    trackDeletedAccountOrders: false,
                    trackViewedProduct: true,
                    trackRecentlyViewedItems: true,
                    trackAddedToCart: true,
                    trackStartedCheckout: true,
                    trackPlacedOrder: true,
                    trackOrderedProduct: true,
                    trackFulfilledOrder: true,
                    trackCancelledOrder: true,
                    trackRefundedOrder: true,
                    trackPaidOrder: false,
                    trackSubscribedToBackInStock: true,
                    isInitializeKlaviyoAfterInteraction: true,
                    popUpOpenBtnColor: '',
                    popUpOpenBtnBgColor: '',
                    popUpCloseBtnColor: '',
                    popUpCloseBtnBgColor: '',
                    subscribeBtnColor: '',
                    subscribeBtnBgColor: '',
                    popUpAdditionalClasses: '',
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
        },

        validateApiCredentials() {
            this.apiValidationInProgress = true;
            const privateKey = this.actualConfigData['KlaviyoIntegrationPlugin.config.privateApiKey'];
            const publicKey = this.actualConfigData['KlaviyoIntegrationPlugin.config.publicApiKey'];
            const list = this.actualConfigData['KlaviyoIntegrationPlugin.config.klaviyoListForSubscribersSync'];

            if (!(this.credentialsEmptyValidation('privateApiKey', privateKey) * this.credentialsEmptyValidation('publicApiKey', publicKey) * this.credentialsEmptyValidation('klaviyoListForSubscribersSync', list))) {
                this.apiValidationInProgress = false;
                return;
            }

            this.klaviyoApiKeyValidatorService.validate(privateKey, publicKey, list).then((response) => {
                if (response.status !== 200) {
                    this.createNotificationError({
                        message: this.$tc('klaviyo-integration-settings.configs.apiValidation.generalErrorMessage'),
                    });
                    return;
                }
                const data = response.data;

                if (data.success) {
                    this.createNotificationSuccess({
                        title: this.$root.$tc('global.default.success'),
                        message: this.$tc('klaviyo-integration-settings.configs.apiValidation.correctApiMessage'),
                    });
                } else if (data.general_error) {
                    this.createNotificationError({
                        message: this.$tc('klaviyo-integration-settings.configs.apiValidation.generalErrorMessage'),
                    });
                } else if (data.incorrect_credentials) {
                    this.createNotificationError({
                        title: this.$tc('klaviyo-integration-settings.configs.apiValidation.incorrectCredentialsTitle'),
                        message: data.incorrect_credentials_message,
                    });
                } else if (data.incorrect_list) {
                    this.createNotificationWarning({
                        message: this.$tc('klaviyo-integration-settings.configs.apiValidation.listNotExistMessage'),
                    });
                }
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('klaviyo-integration-settings.configs.apiValidation.generalErrorMessage'),
                });
            }).finally(() => {
                this.apiValidationInProgress = false;
            });
        },

        credentialsEmptyValidation(key, value) {
            if (value === undefined || value === '' || value === null) {
                this.createNotificationError({
                    message: this.$tc('klaviyo-integration-settings.configs.apiValidation.emptyErrorMessage', 0, {entityName: this.$tc('klaviyo-integration-settings.configs.' + key + '.label')}),
                });
                return false
            }
            return true;
        }
    },
});
