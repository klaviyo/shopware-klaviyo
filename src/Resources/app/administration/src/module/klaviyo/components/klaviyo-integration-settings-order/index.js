import { reactive } from 'vue';
import template from './klaviyo-integration-settings-order.html.twig';
import './klaviyo-integration-settings-order.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('klaviyo-integration-settings-order', {
    template,

    props: {
        allConfigs: {
            type: Object,
            required: true
        },
        mappingErrorStates: {
            type: Object,
            required: true
        }
    },

    inject: [
        'repositoryFactory'
    ],

    created() {
        this.createdComponent();
    },

    data() {
        return {
            isLoading: false,
            isSomeMappingsNotFilled: false,
            systemCustomFields: null,
            localeIso: null,
            configPath: 'klavi_overd.config.orderFieldMapping'
        };
    },

    computed: {
        customFieldRepository() {
            return this.repositoryFactory.create('custom_field');
        },

        customFieldCriteria() {
            const criteria = new Criteria();
            criteria.addSorting(Criteria.sort('name', 'ASC', true));
            criteria.addFilter(Criteria.equals('customFieldSet.relations.entityName', 'order'));
            criteria.addFilter(Criteria.equals('active', 1));
            criteria.addAssociation('customFieldSet');
            return criteria;
        },

        noCustomFieldsError() {
            if (this.systemCustomFields?.total === 0) {
                return this.$tc('klaviyo-integration-settings.order.fieldMapping.noMappingFieldsError');
            }
            return null;
        }
    },

    watch: {
        'allConfigs.null.Klaviyo\\.config\\.orderFieldMapping': {
            handler(mappingConfig) {
                Object.keys(mappingConfig).forEach((mappingKey) => {
                    if (mappingConfig[mappingKey].active && !mappingConfig[mappingKey].customLabel) {
                        this.mappingErrorStates[mappingKey] = {
                            code: 1,
                            detail: this.$tc('klaviyo-integration-settings.order.fieldMapping.labelNotFilledError')
                        };
                    } else {
                        delete this.mappingErrorStates[mappingKey];
                    }
                });
            },
            deep: true
        }
    },

    methods: {
        createdComponent() {
            this.isLoading = true;
            this.localeIso = Context.app.fallbackLocale;

            // Initialize configuration if undefined or array
            if (!this.allConfigs['null'][this.configPath] || Array.isArray(this.allConfigs['null'][this.configPath])) {
                this.allConfigs['null'][this.configPath] = {};
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

            Object.keys(this.allConfigs['null'][this.configPath]).forEach(mappingKey => {
                if (this.allConfigs['null'][this.configPath][mappingKey].active && !this.allConfigs['null'][this.configPath][mappingKey].customLabel) {
                    this.mappingErrorStates[mappingKey] = {
                        code: 1,
                        detail: this.$tc('klaviyo-integration-settings.order.fieldMapping.labelNotFilledError')
                    };
                } else {
                    this.mappingErrorStates[mappingKey] = {};
                }

                existingCustomFieldNames.push(this.allConfigs['null'][this.configPath][mappingKey].customFieldName);

                if (!systemFieldNames.includes(this.allConfigs['null'][this.configPath][mappingKey].customFieldName)) {
                    delete this.mappingErrorStates[mappingKey];
                    delete this.allConfigs['null'][this.configPath][mappingKey];
                }
            });

            systemFieldNames.forEach((systemFieldName) => {
                if (!existingCustomFieldNames.includes(systemFieldName)) {
                    const systemField = this.systemCustomFields.find(field => field.name === systemFieldName);
                    if (systemField) {
                        this.addNewEmptyFieldMapping(systemField);
                    }
                }
            });
        },

        getCustomFieldHint(mappingKey) {
            const systemFieldName = this.allConfigs['null'][this.configPath][mappingKey]?.customFieldName;
            const systemField = this.systemCustomFields.find(field => field.name === systemFieldName) ?? {};

            return systemField?.config?.label[this.localeIso] ?? systemField?.name ?? '<not_found>';
        },

        addNewEmptyFieldMapping(field) {
            const mappingKey = 'mapping_' + this.generateGuid();
            this.allConfigs['null'][this.configPath][mappingKey] = {
                customLabel: '',
                customFieldName: field.name,
                active: false
            };
            this.mappingErrorStates[mappingKey] = {};
        },

        generateGuid() {
            const s4 = () => Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1);
            return `${s4()}${s4()}-${s4()}-${s4()}-${s4()}-${s4()}${s4()}${s4()}`;
        }
    }
});