import template from './klaviyo-integration-settings.html.twig';
import './klaviyo-integration-settings.scss';

const {Component, Defaults} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('klaviyo-integration-settings', {
    template,

    inject: [
        'repositoryFactory',
        'klaviyoApiKeyValidatorService',
    ],

    mixins: [
        'notification',
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            isListIdPresent: false,
            privateKeyFilled: false,
            publicKeyFilled: false,
            listIdFilled: false,
            messageBlankErrorState: null,
            mappingErrorStates: {},
            config: null,
            savedListId: null,
            salesChannels: []
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        salesChannelRepository() {
            return this.repositoryFactory.create('sales_channel');
        },

        salesChannelCriteria() {
            // Limit of 500 is fine according same limits on Shopware's official PayPal plugin.
            const criteria = new Criteria(1, 500);
            criteria.addFilter(Criteria.equalsAny('typeId', [
                Defaults.storefrontSalesChannelTypeId,
                Defaults.apiSalesChannelTypeId,
            ]));

            return criteria;
        },

        privateKeyErrorState() {
            if (this.privateKeyFilled) {
                return null;
            }

            return this.messageBlankErrorState;
        },

        publicKeyErrorState() {
            if (this.publicKeyFilled) {
                return null;
            }

            return this.messageBlankErrorState;
        },

        listIdErrorState() {
            if (this.listIdFilled) {
                return null;
            }

            return this.messageBlankErrorState;
        },

        hasError() {
            const hasMappingErrors = Object.values(this.mappingErrorStates)
                .filter((state) => state.code !== undefined)
                .length !== 0;

            return !this.privateKeyFilled
                || !this.publicKeyFilled
                || !this.listIdFilled
                || hasMappingErrors;
        }
    },

    watch: {
        config: {
            handler() {
                const channelId = this.$refs.configComponent.selectedSalesChannelId;
                const accountEnabled = !!this.config['klavi_overd.config.enabled'];

                if (channelId !== null && accountEnabled) {
                    this.privateKeyFilled = !!this.config['klavi_overd.config.privateApiKey'];
                    this.publicKeyFilled = !!this.config['klavi_overd.config.publicApiKey'];
                    this.listIdFilled = !!this.config['klavi_overd.config.klaviyoListForSubscribersSync'];

                    if (!this.savedListId) {
                        this.savedListId = this.config['klavi_overd.config.klaviyoListForSubscribersSync'];
                    } else {
                        if (this.savedListId !== this.config['klavi_overd.config.klaviyoListForSubscribersSync']) {
                            if (this.privateKeyFilled && this.publicKeyFilled && this.listIdFilled) {
                                this.validateNewsletterListId(this.config['klavi_overd.config.klaviyoListForSubscribersSync']);
                                this.savedListId = this.config['klavi_overd.config.klaviyoListForSubscribersSync'];
                            }
                        }
                    }
                } else {
                    this.privateKeyFilled = this.publicKeyFilled = this.listIdFilled = true;
                }
            },
            deep: true,
        },

        newsletterListId: {
            handler() {
                const channelId = this.$refs.configComponent.selectedSalesChannelId;
                const accountEnabled = !!this.config['klavi_overd.config.enabled'];

                if (channelId !== null && accountEnabled) {
                    if (this.privateKeyFilled && this.publicKeyFilled && this.listIdFilled) {
                        this.validateNewsletterListId(this.config['klavi_overd.config.klaviyoListForSubscribersSync']);
                    }
                }
            },
            deep: true,
        }
    },

    methods: {
        createdComponent() {
            this.getSalesChannels();

            this.messageBlankErrorState = {
                code: 1,
                detail: this.$tc('klaviyo-integration-settings.configs.credentials.messageNotBlank'),
            };
        },

        onChangeLanguage() {
            this.getSalesChannels();
        },

        getSalesChannels() {
            this.isLoading = true;
            this.salesChannelRepository.search(this.salesChannelCriteria, Shopware.Context.api).then(res => {
                res.add({
                    id: null,
                    translated: {
                        name: this.$tc('sw-sales-channel-switch.labelDefaultOption'),
                    },
                });

                this.salesChannels = res;
            }).finally(() => {
                this.isLoading = false;
            });
        },

        onSave() {
            if (this.hasError) {
                return;
            }

            this.isLoading = true;

            const newsletterListId = this.config['klavi_overd.config.klaviyoListForSubscribersSync'];

            if (newsletterListId) {
               if (!this.isListIdPresent) {
                   this.isLoading = false;
                   this.isSaveSuccessful = false;
                   return;
               }
            }

            this.$refs.configComponent.save().then(() => {
                this.isSaveSuccessful = true;
            }).finally(() => {
                this.isLoading = false;
            });
        },

        validateNewsletterListId(newsletterListId) {
            const privateKey = this.config['klavi_overd.config.privateApiKey'];
            const publicKey = this.config['klavi_overd.config.publicApiKey'];
            this.isListIdPresent = false;

            this.klaviyoApiKeyValidatorService.validateListById(privateKey, publicKey, newsletterListId).then((response) => {
                if (!response.data || !response.data.data) {
                    this.isListIdPresent = false;
                    this.createNotificationError({
                        message: this.$tc('klaviyo-integration-settings.configs.apiValidation.listNotExistMessage'),
                    });
                }

                if (response.data && response.data.data && response.data.data[0].value === newsletterListId) {
                    this.isListIdPresent = true;
                }

            }).catch(() => {
                this.isListIdPresent = false;
                this.createNotificationError({
                    message: this.$tc('klaviyo-integration-settings.configs.apiValidation.listNotExistMessage'),
                });
            });
        }
    }
});
