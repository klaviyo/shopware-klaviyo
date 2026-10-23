import template from './klaviyo-integration-settings-general.html.twig';
import './klaviyo-integration-settings-general.scss';

const {Component, Mixin} = Shopware;

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
        listIdErrorState: {
            type: Object,
            required: false,
            default: null,
        },
    },

    emits: ['mapping-error-states-changed'],

    watch: {
        selectedSalesChannelId: {
            deep: true,
            immediate: true,
            handler: function(v) {
                this.salesChannelSwitched(v);
            }
        },
        apiKeys: {
            deep: true,
            handler: function(newValue, oldValue) {
                if (newValue.privateKey && (newValue !== oldValue)) {
                    this.areListsLoading = true;
                    this.debounce(() => {
                        this.fetchKlaviyoLists().then(() => { this.areListsLoading = false; })
                    }, 500);
                } else {
                    this.listsOptions = [];
                }
            },
        },

        mappingErrorStates: {
            immediate: true,
            handler(newValue) {
                this.$emit('mapping-error-states-changed', newValue);
            },
        },
    },

    data() {
        return {
            activeTab: 'general',
            selectedSubscriptionList: null,
            subscriptionListOptions: [],
            isLoading: false,
            areListsLoading: true,
            apiValidationInProgress: false,
            listsOptions: [],
            debounceTimeoutId: null,
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
                },
                {
                    name: 'Usercentrics',
                    value: 'usercentrics'
                }
            ]
        };
    },

    computed: {
        apiKeys() {
            return {
                privateKey: this.actualConfigData['klavi_overd.config.privateApiKey'],
            };
        },
        createBisVariantFieldOptions() {
            return [
                {
                    label: Shopware.Snippet.tc('klaviyo-integration-settings.configs.bisVariantField.productId'),
                    value: 'product-id'
                },
                {
                    label: Shopware.Snippet.tc('sw-product.basicForm.labelProductNumber'),
                    value: 'product-number'
                }
            ]
        },
        createOrderIdentificationFieldOptions() {
            return [
                {
                    label: Shopware.Snippet.tc('klaviyo-integration-settings.configs.orderIdentification.orderId'),
                    value: 'order-id'
                },
                {
                    label: Shopware.Snippet.tc('klaviyo-integration-settings.configs.orderIdentification.orderNumber'),
                    value: 'order-number'
                }
            ]
        },
        createCookieConsentOptions() {
            return [
                {
                    label: Shopware.Snippet.tc('klaviyo-integration-settings.configs.cookieConsent.nothingLabel'),
                    value: 'nothing'
                },
                {
                    label: Shopware.Snippet.tc('klaviyo-integration-settings.configs.cookieConsent.shopwareLabel'),
                    value: 'shopware'
                },
                {
                    label: Shopware.Snippet.tc('klaviyo-integration-settings.configs.cookieConsent.cookieBotLabel'),
                    value: 'cookiebot'
                },
                {
                    label: Shopware.Snippet.tc('klaviyo-integration-settings.configs.cookieConsent.consentManagerLabel'),
                    value: 'consentmanager'
                },
                {
                    label: this.$tc('klaviyo-integration-settings.configs.cookieConsent.usercentricsLabel'),
                    value: 'usercentrics'
                }
            ]
        },
        createOldJobCleanupPeriodOptions() {
            let dayPeriods = [5, 10, 15, 20, 30, 60, 90];
            let options = [];

            for (let dayPeriodsKey in dayPeriods) {
                options.push(
                    {
                        label: Shopware.Snippet.tc('klaviyo-integration-settings.configs.oldJobCleanupPeriod.after') + ' ' +
                            dayPeriods[dayPeriodsKey] + ' ' +
                            Shopware.Snippet.tc('klaviyo-integration-settings.configs.oldJobCleanupPeriod.days'),
                        value: dayPeriods[dayPeriodsKey]
                    }
                )
            }

            return options;
        },
        createListsOptions() {
            return this.listsOptions;
        },

        // Validates customer/order field mappings directly from allConfigs, independent of
        // whether the Mapping tab (and its klaviyo-integration-settings-customer/-order
        // components) has ever been mounted.
        mappingErrorStates() {
            return {
                ...this.buildFieldMappingErrors(
                    'klavi_overd.config.customerFieldMapping',
                    Shopware.Snippet.tc('klaviyo-integration-settings.customer.fieldMapping.labelNotFilledError'),
                ),
                ...this.buildFieldMappingErrors(
                    'klavi_overd.config.orderFieldMapping',
                    Shopware.Snippet.tc('klaviyo-integration-settings.order.fieldMapping.labelNotFilledError'),
                ),
            };
        },
    },

    created() {
        this.createdComponent();
        this.fetchKlaviyoLists().then(() => { this.areListsLoading = false; })
    },

    methods: {
        salesChannelSwitched(v) {
            if (!v) {
                this.selectedSubscriptionList = null;
            }
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
                    cookieConsent: 'shopware',
                    oldJobCleanupPeriod: 5,
                    withoutSubscribersSync: false,
                    withoutOrdersSync: false
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
            const privateKey = this.actualConfigData['klavi_overd.config.privateApiKey'];
            const publicKey = this.actualConfigData['klavi_overd.config.publicApiKey'];
            const listId = this.actualConfigData['klavi_overd.config.klaviyoListForSubscribersSync'];

            if (!(this.credentialsEmptyValidation('privateApiKey', privateKey) * this.credentialsEmptyValidation('publicApiKey', publicKey))) {
                this.apiValidationInProgress = false;
                return;
            }

            this.klaviyoApiKeyValidatorService.validate(privateKey, publicKey, listId).then((response) => {
                if (response.status !== 200) {
                    this.createNotificationError({
                        message: Shopware.Snippet.tc('klaviyo-integration-settings.configs.apiValidation.generalErrorMessage'),
                    });
                    return;
                }
                const data = response.data;

                if (data.success) {
                    this.createNotificationSuccess({
                        title: this.$root.$tc('global.default.success'),
                        message: Shopware.Snippet.tc('klaviyo-integration-settings.configs.apiValidation.correctApiMessage'),
                    });
                } else if (data.general_error) {
                    this.createNotificationError({
                        message: Shopware.Snippet.tc('klaviyo-integration-settings.configs.apiValidation.generalErrorMessage'),
                    });
                    this.storeSelectedListValue(null);
                } else if (data.incorrect_credentials) {
                    this.createNotificationError({
                        title: Shopware.Snippet.tc('klaviyo-integration-settings.configs.apiValidation.incorrectCredentialsTitle'),
                        message: data.incorrect_credentials_message,
                    });
                    this.storeSelectedListValue(null);
                }
            }).catch(() => {
                this.createNotificationError({
                    message: Shopware.Snippet.tc('klaviyo-integration-settings.configs.apiValidation.generalErrorMessage'),
                });
            }).finally(() => {
                this.apiValidationInProgress = false;
            });
        },

        credentialsEmptyValidation(key, value) {
            if (value === undefined || value === '' || value === null) {
                this.createNotificationError({
                    message: Shopware.Snippet.tc('klaviyo-integration-settings.configs.apiValidation.emptyErrorMessage', 0, {entityName: Shopware.Snippet.tc('klaviyo-integration-settings.configs.' + key + '.label')}),
                });
                return false
            }
            return true;
        },
        async fetchKlaviyoLists() {
            const privateKey = this.actualConfigData['klavi_overd.config.privateApiKey'];

            if (!privateKey) {
                this.listsOptions = [];
                return;
            }

            try {
                const response = await this.klaviyoApiKeyValidatorService.getList(privateKey);

                if (response.status !== 200) {
                    this.createNotificationError({
                        message: this.$tc('klaviyo-integration-settings.configs.lists.error'),
                    });
                    this.listsOptions = [];
                    return;
                }

                const data = response.data.data;

                if (data.length === 0) {
                    this.createNotificationError({
                        message: this.$tc('klaviyo-integration-settings.configs.lists.warning'),
                    });
                    this.listsOptions = [];
                    return;
                }

                this.listsOptions = data.map((item) => ({
                    label: item.label,
                    value: item.value,
                }));

                if (this.listsOptions.length > 0 && !this.actualConfigData['klavi_overd.config.klaviyoListForSubscribersSync']) {
                    this.actualConfigData['klavi_overd.config.klaviyoListForSubscribersSync'] = this.listsOptions[0].value;
                }
            } catch (error) {
                this.createNotificationError({
                    message: this.$tc('klaviyo-integration-settings.configs.lists.error'),
                });
                this.listsOptions = [];
            }
        },

        debounce(callback, delay) {
            if (this.debounceTimeoutId) {
                clearTimeout(this.debounceTimeoutId);
            }
            this.debounceTimeoutId = setTimeout(callback, delay);
        },

        storeSelectedListValue(value) {
            this.selectedSubscriptionList = value;
        },

        buildFieldMappingErrors(configPath, errorDetail) {
            const mappingConfig = this.allConfigs?.['null']?.[configPath];
            const errorStates = {};

            if (!mappingConfig || typeof mappingConfig !== 'object' || Array.isArray(mappingConfig)) {
                return errorStates;
            }

            Object.keys(mappingConfig).forEach((mappingKey) => {
                const mapping = mappingConfig[mappingKey];
                if (mapping && mapping.active && !mapping.customLabel) {
                    errorStates[mappingKey] = { code: 1, detail: errorDetail };
                }
            });

            return errorStates;
        },
    },
});
