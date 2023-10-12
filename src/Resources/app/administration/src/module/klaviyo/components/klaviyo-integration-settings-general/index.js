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

    watch: {
        selectedSalesChannelId: {
            deep: true,
            immediate: true,
            handler: function(v) {
                this.salesChannelSwitched(v);
            }
        },
        actualConfigData: {
            deep: true,
            immediate: true,
            handler: function(v) {
                this.bringSubscribersList(v);
            },
        }
    },

    data() {
        return {
            selectedSubscriptionList: null,
            subscriptionListOptions: [],
            isLoading: false,
            apiValidationInProgress: false,
            cookieConsentOptions: [
                {
                    name: 'Nothing',
                    value: 'nothing'
                },
                {
                    name: 'Shopware default',
                    value: 'shopware'
                },
                {
                    name: 'CookieBot',
                    value: 'cookiebot'
                },
                {
                    name: 'ConsentManager',
                    value: 'consentmanager'
                }
            ]
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
        createOrderIdentificationFieldOptions() {
            return [
                {
                    label: this.$tc('klaviyo-integration-settings.configs.orderIdentification.orderId'),
                    value: 'order-id'
                },
                {
                    label: this.$tc('klaviyo-integration-settings.configs.orderIdentification.orderNumber'),
                    value: 'order-number'
                }
            ]
        },
        createCookieConsentOptions() {
            return [
                {
                    label: this.$tc('klaviyo-integration-settings.configs.cookieConsent.nothingLabel'),
                    value: 'nothing'
                },
                {
                    label: this.$tc('klaviyo-integration-settings.configs.cookieConsent.shopwareLabel'),
                    value: 'shopware'
                },
                {
                    label: this.$tc('klaviyo-integration-settings.configs.cookieConsent.cookieBotLabel'),
                    value: 'cookiebot'
                },
                {
                    label: this.$tc('klaviyo-integration-settings.configs.cookieConsent.consentManagerLabel'),
                    value: 'consentmanager'
                }
            ]
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        salesChannelSwitched(v) {
            if (v) {
                this.bringSubscribersList();
            } else {
                this.selectedSubscriptionList = null;
            }
        },

        bringSubscribersList(v) {
            this.setSubscriptionListOptions();
        },

        createdComponent() {
            const configPrefix = 'klavi_overd.config.',
                defaultConfigs = {
                    enabled: false,
                    bisVariantField: 'product-number',
                    orderIdentification: 'order-id',
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
                    trackShippedOrder: false,
                    trackSubscribedToBackInStock: true,
                    isInitializeKlaviyoAfterInteraction: true,
                    popUpOpenBtnColor: '',
                    popUpOpenBtnBgColor: '',
                    popUpCloseBtnColor: '',
                    popUpCloseBtnBgColor: '',
                    subscribeBtnColor: '',
                    subscribeBtnBgColor: '',
                    popUpAdditionalClasses: '',
                    cookieConsent: 'shopware'
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

        setSubscriptionListOptions: function () {
            const privateKey = this.actualConfigData['klavi_overd.config.privateApiKey'];
            const publicKey = this.actualConfigData['klavi_overd.config.publicApiKey'];

            this.klaviyoApiKeyValidatorService.getList(privateKey, publicKey).then((response) => {

                if (response.data.incorrect_list) {
                    this.createNotificationWarning({
                        message: this.$tc('klaviyo-integration-settings.configs.apiValidation.listNotExistMessage'),
                    });
                }

                let options = [];
                for (let i = 0; i < response.data.data.length; i++) {
                    options.push(response.data.data[i]);
                }
                this.subscriptionListOptions = options;
                this.selectedSubscriptionList = this.actualConfigData['klavi_overd.config.klaviyoListForSubscribersSync'];

            }).catch(() => {
            });
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
            const privateKey = this.actualConfigData['klavi_overd.config.privateApiKey'];
            const publicKey = this.actualConfigData['klavi_overd.config.publicApiKey'];
            const list = this.actualConfigData['klavi_overd.config.klaviyoListForSubscribersSync'];

            if (!(this.credentialsEmptyValidation('privateApiKey', privateKey) * this.credentialsEmptyValidation('publicApiKey', publicKey))) {
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
                    this.storeSelectedListValue(null);
                } else if (data.incorrect_credentials) {
                    this.createNotificationError({
                        title: this.$tc('klaviyo-integration-settings.configs.apiValidation.incorrectCredentialsTitle'),
                        message: data.incorrect_credentials_message,
                    });
                    this.storeSelectedListValue(null);
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
        },

        storeSelectedListValue(val) {
            this.selectedSubscriptionList = val;
            if (val) {
                this.actualConfigData['klavi_overd.config.klaviyoListForSubscribersSync'] = val;
            }
        }
    },
});
