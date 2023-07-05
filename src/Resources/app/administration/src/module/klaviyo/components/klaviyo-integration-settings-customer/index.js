import template from './klaviyo-integration-settings-customer.html.twig';
import './klaviyo-integration-settings-customer.scss';

const {Component, Context} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('klaviyo-integration-settings-customer', {
    template,

    props: {
        allConfigs: {
            type: Object,
            required: true,
        },
        mappingErrorStates: {
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
            isSomeMappingsNotFilled: false,
            systemCustomFields: null,
            // mappingErrorStates: {},
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

        noCustomFieldsError() {
            if (this.systemCustomFields.total === 0) {
                return this.$tc('klaviyo-integration-settings.customer.fieldMapping.noMappingFieldsError');
            }

            return null;
        },

        customFieldMapping: {
            get: function () {
                return this.allConfigs['null'][this.configPath];
            }
        }
    },

    watch: {
        customFieldMapping: {
            handler() {
                const mappingConfig = this.allConfigs['null'][this.configPath];

                Object.keys(mappingConfig).every((mappingKey) => {
                    if (mappingConfig[mappingKey].active && !mappingConfig[mappingKey].customLabel) {
                        this.$set(this.mappingErrorStates, mappingKey, {
                            code: 1,
                            detail: this.$tc('klaviyo-integration-settings.customer.fieldMapping.labelNotFilledError'),
                        });
                    } else {
                        this.$delete(this.mappingErrorStates, mappingKey);
                    }
                    return true;
                });
            },
            deep: true,
        },
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            this.localeIso = Context.app.fallbackLocale;

            if (this.customFieldMapping === undefined || Array.isArray(this.customFieldMapping)) {
                /**
                 * Initialize configuration.
                 */
                this.$set(this.allConfigs['null'], this.configPath, {});
            }

            this.customFieldRepository.search(this.customFieldCriteria, Context.api)
                .then((customFields) => {
                    this.systemCustomFields = customFields;
                })
                .finally(() => {
                    this.processCustomFieldMappings();
                    this.isLoading = false;
                });
        },

        processCustomFieldMappings() {
            let existingCustomFieldNames = [];
            const systemFieldNames = this.systemCustomFields.map((systemField) => systemField.name);

            Object.keys(this.customFieldMapping).forEach(mappingKey => {
                if (this.customFieldMapping[mappingKey].active && !this.customFieldMapping[mappingKey].customLabel) {
                    this.$set(this.mappingErrorStates, mappingKey, {
                        code: 1,
                        detail: this.$tc('klaviyo-integration-settings.customer.fieldMapping.labelNotFilledError'),
                    });
                } else {
                    this.$set(this.mappingErrorStates, mappingKey, {});
                }

                existingCustomFieldNames.push(this.customFieldMapping[mappingKey].customFieldName);

                if (systemFieldNames.indexOf(this.customFieldMapping[mappingKey]['customFieldName']) === -1) {
                    this.$delete(this.mappingErrorStates, mappingKey);
                    this.$delete(this.customFieldMapping, mappingKey);
                }
            });

            systemFieldNames.forEach((systemFieldName) => {
                if (!existingCustomFieldNames.includes(systemFieldName)) {
                    this.addNewEmptyFieldMapping(this.systemCustomFields.filter((systemField) => systemField.name === systemFieldName)[0]);
                }
            });
        },

        getCustomFieldHint(mappingKey) {
            const systemFieldName = this.customFieldMapping[mappingKey]['customFieldName'];
            const systemField = this.systemCustomFields.filter((systemField) => systemField.name === systemFieldName)[0] ?? {};

            return systemField?.config?.label[this.localeIso] ?? systemField?.name ?? '<not_found>';
        },

        addNewEmptyFieldMapping(field) {
            const mappingKey = 'mapping_' + this.generateGuid();
            this.$set(this.customFieldMapping, mappingKey, {
                customLabel: '',
                customFieldName: field.name,
                active: false
            });
            this.$set(this.mappingErrorStates, mappingKey, {});
        },

        generateGuid() {
            let s4 = () => {
                return Math.floor((1 + Math.random()) * 0x10000)
                    .toString(16)
                    .substring(1);
            }

            return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
        }
    },
});
