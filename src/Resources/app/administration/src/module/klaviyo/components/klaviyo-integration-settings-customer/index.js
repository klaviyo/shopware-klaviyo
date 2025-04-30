import template from './klaviyo-integration-settings-customer.html.twig';
import './klaviyo-integration-settings-customer.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

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
        },
    },

    inject: ['repositoryFactory'],

    created() {
        this.createdComponent();
    },

    data() {
        return {
            isLoading: false,
            isSomeMappingsNotFilled: false,
            systemCustomFields: null,
            configPath: 'klavi_overd.config.customerFieldMapping',
        };
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
            if (this.systemCustomFields?.total === 0) {
                return this.$tc('klaviyo-integration-settings.customer.fieldMapping.noMappingFieldsError');
            }

            return null;
        },

        customFieldMapping: {
            get() {
                return this.allConfigs['null'][this.configPath];
            },
        },
    },

    watch: {
        customFieldMapping: {
            handler() {
                const mappingConfig = this.allConfigs['null'][this.configPath];

                Object.keys(mappingConfig).forEach((mappingKey) => {
                    if (mappingConfig[mappingKey].active && !mappingConfig[mappingKey].customLabel) {
                        this.mappingErrorStates[mappingKey] = {
                            code: 1,
                            detail: this.$tc('klaviyo-integration-settings.customer.fieldMapping.labelNotFilledError'),
                        };
                    } else {
                        delete this.mappingErrorStates[mappingKey];
                    }
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
                // Initialize configuration
                this.allConfigs['null'][this.configPath] = {};
            }

            this.customFieldRepository
                .search(this.customFieldCriteria, Context.api)
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

            Object.keys(this.customFieldMapping).forEach((mappingKey) => {
                if (this.customFieldMapping[mappingKey].active && !this.customFieldMapping[mappingKey].customLabel) {
                    this.mappingErrorStates[mappingKey] = {
                        code: 1,
                        detail: this.$tc('klaviyo-integration-settings.customer.fieldMapping.labelNotFilledError'),
                    };
                } else {
                    this.mappingErrorStates[mappingKey] = {};
                }

                existingCustomFieldNames.push(this.customFieldMapping[mappingKey].customFieldName);

                if (!systemFieldNames.includes(this.customFieldMapping[mappingKey].customFieldName)) {
                    delete this.mappingErrorStates[mappingKey];
                    delete this.customFieldMapping[mappingKey];
                }
            });

            systemFieldNames.forEach((systemFieldName) => {
                if (!existingCustomFieldNames.includes(systemFieldName)) {
                    this.addNewEmptyFieldMapping(
                        this.systemCustomFields.find((systemField) => systemField.name === systemFieldName)
                    );
                }
            });
        },

        getCustomFieldHint(mappingKey) {
            const systemFieldName = this.customFieldMapping[mappingKey].customFieldName;
            const systemField = this.systemCustomFields.find((systemField) => systemField.name === systemFieldName) ?? {};

            return systemField?.config?.label[this.localeIso] ?? systemField?.name ?? '<not_found>';
        },

        addNewEmptyFieldMapping(field) {
            const mappingKey = 'mapping_' + this.generateGuid();
            this.customFieldMapping[mappingKey] = {
                customLabel: '',
                customFieldName: field.name,
                active: false,
            };
            this.mappingErrorStates[mappingKey] = {};
        },

        generateGuid() {
            const s4 = () =>
                Math.floor((1 + Math.random()) * 0x10000)
                    .toString(16)
                    .substring(1);

            return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
        },
    },
});