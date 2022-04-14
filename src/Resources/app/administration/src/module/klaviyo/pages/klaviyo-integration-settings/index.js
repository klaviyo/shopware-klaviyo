import template from './klaviyo-integration-settings.html.twig';
import './klaviyo-integration-settings.scss';

const {Component, Defaults} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('klaviyo-integration-settings', {
    template,

    inject: [
        'repositoryFactory',
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            privateKeyFilled: false,
            publicKeyFilled: false,
            listNameFilled: false,
            messageBlankErrorState: null,
            mappingErrorStates: {},
            config: null,
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

        listNameErrorState() {
            if (this.listNameFilled) {
                return null;
            }

            return this.messageBlankErrorState;
        },

        hasError() {
            const hasMappingErrors = Object.values(this.mappingErrorStates)
                .filter((state) => state.code !== undefined)
                .length !== 0;

            return hasMappingErrors;
            //TODO: we will use this in future
            // return !this.privateKeyFilled
            //     || !this.publicKeyFilled
            //     || !this.listNameFilled
            //     || hasMappingErrors;
        }
    },

    watch: {
        config: {
            handler() {
                const defaultConfig = this.$refs.configComponent.allConfigs.null;
                const salesChannelId = this.$refs.configComponent.selectedSalesChannelId;

                if (salesChannelId === null) {
                    this.privateKeyFilled = !!this.config['KlaviyoIntegrationPlugin.config.privateApiKey'];
                    this.publicKeyFilled = !!this.config['KlaviyoIntegrationPlugin.config.publicApiKey'];
                    this.listNameFilled = !!this.config['KlaviyoIntegrationPlugin.config.klaviyoListForSubscribersSync'];
                } else {
                    this.privateKeyFilled = !!this.config['KlaviyoIntegrationPlugin.config.privateApiKey']
                        || !!defaultConfig['KlaviyoIntegrationPlugin.config.privateApiKey'];
                    this.publicKeyFilled = !!this.config['KlaviyoIntegrationPlugin.config.publicApiKey']
                        || !!defaultConfig['KlaviyoIntegrationPlugin.config.publicApiKey'];
                    this.listNameFilled = !!this.config['KlaviyoIntegrationPlugin.config.klaviyoListForSubscribersSync']
                        || !!defaultConfig['KlaviyoIntegrationPlugin.config.klaviyoListForSubscribersSync'];
                }
            },
            deep: true,
        },
    },

    methods: {
        createdComponent() {
            this.getSalesChannels();

            this.messageBlankErrorState = {
                code: 1,
                detail: this.$tc('klaviyo-integration-settings.general.messageNotBlank'),
            };
        },

        onChangeLanguage() {
            this.getSalesChannels();
        },

        getSalesChannels() {
            this.isLoading = true;

            const criteria = new Criteria();
            criteria.addFilter(Criteria.equalsAny('typeId', [
                Defaults.storefrontSalesChannelTypeId,
                Defaults.apiSalesChannelTypeId,
            ]));

            this.salesChannelRepository.search(criteria, Shopware.Context.api).then(res => {
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

            this.$refs.configComponent.save().then(() => {
                this.isSaveSuccessful = true;
            }).finally(() => {
                this.isLoading = false;
            });
        }
    }
});
