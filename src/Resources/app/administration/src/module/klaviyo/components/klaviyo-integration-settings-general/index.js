import template from './klaviyo-integration-settings-general.html.twig';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('klaviyo-integration-settings-general', {
    template,

    inject: [
        'repositoryFactory',
    ],

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

    created() {
        this.createdComponent();
    },

    computed: {
        languageRepository() {
            return this.repositoryFactory.create('language');
        }
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            const criteria = new Criteria();
            criteria.addSorting(Criteria.sort('createdAt', 'ASC'));

            this.languageRepository.search(criteria, Shopware.Context.api).then(result => {
                this.systemLanguages = result;
                this.initLanguageConfig();
            }).finally(() => {
                this.isLoading = false;
            });
        },

        initLanguageConfig() {
            if (this.allConfigs[this.selectedSalesChannelId][this.configPath] === undefined) {
                this.$set(this.allConfigs[this.selectedSalesChannelId], 'KlaviyoIntegrationPlugin.config', {});
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
