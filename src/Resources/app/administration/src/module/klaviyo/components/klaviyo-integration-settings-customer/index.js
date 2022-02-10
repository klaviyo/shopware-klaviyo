import template from './klaviyo-integration-settings-customer.html.twig';
import './klaviyo-integration-settings-customer.scss';

const {Component, Context} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('klaviyo-integration-settings-customer', {
    template,

    props: {
        actualConfigData: {
            type: Object,
            required: true,
        },
        allConfigs: {
            type: Object,
            required: true,
        }
    },

    inject: [
        'repositoryFactory',
    ],

    created() {
        this.createdComponent();
    },

    data() {
        return {
            isLoading: false,
            systemCustomFields: null,
            configPath: 'KlaviyoIntegrationPlugin.config.customerFieldMapping'
        }
    },

    computed: {
        customFieldRepository() {
            return this.repositoryFactory.create('custom_field');
        },
        customFieldCriteria() {
            const criteria = new Criteria();
            criteria.addSorting(Criteria.sort('name', 'ASC', true));
            criteria.addFilter(Criteria.equals('customFieldSet.relations.entityName', 'customer'));
            criteria.addFilter(Criteria.equals('active', 1));
            criteria.addAssociation('customFieldSet');

            return criteria;
        },
        customFieldMapping: {
            get: function () {
                return this.allConfigs['null'][this.configPath];
            }
        }
    },

    methods: {
        createdComponent() {
            if (this.allConfigs['null'][this.configPath] === undefined) {
                /**
                 * Initialize configuration.
                 */
                this.$set(this.allConfigs['null'], this.configPath, {});
            }

            const customFieldsCriteria = new Criteria();
            customFieldsCriteria.addFilter(Criteria.equals('relations.entityName', 'customer'));
            customFieldsCriteria.addAssociation('customFields');
            customFieldsCriteria.addAssociation('relations');

            this.isLoading = true;

            this.customFieldRepository.search(this.customFieldCriteria, Context.api)
                .then((customFields) => {
                    this.systemCustomFields = customFields;
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        onDeleteFieldMapping(mappingKey) {
            this.$delete(this.customFieldMapping, mappingKey);
        },

        onAddNewFieldMapping() {
            const key = 'mapping_' + (Object.keys(this.customFieldMapping).length + 1);
            this.$set(this.customFieldMapping, key, {
                customLabel: '',
                customFieldName: '',
            });
            console.log(this.allConfigs['null'][this.configPath]);
        }
    },
});
