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
            isAddingNewMappingState: false,
            isSomeMappingsNotFilled: false,
            systemCustomFields: null,
            mappingErrorStates: {},
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

        addMappingBtn() {
            return document.getElementById("od-klaviyo-add-mapping-btn");
        },

        noCustomFieldsError() {
            if (this.systemCustomFields.total === 0) {
                return this.$tc('klaviyo-integration-settings.customer.fieldMapping.noMappingFieldsError');
            }

            return null;
        },

        addMappingBtnAvailability() {
            const errorCount = Object.values(this.mappingErrorStates).filter((mappingErrorState) => {
                return Object.keys(mappingErrorState).length !== 0;
            }).length;


            return errorCount !== 0 || this.noCustomFieldsError !== null;
        },

        customFieldMapping: {
            get: function () {
                return this.allConfigs['null'][this.configPath];
            }
        }
    },

    updated: function () {
        this.$nextTick(function () {
            if (this.isAddingNewMappingState) {
                this.addMappingBtn.scrollIntoView({ block: "center", behavior: "smooth" });
                this.isAddingNewMappingState = false;
            }
        })
    },

    watch: {
        customFieldMapping: {
            handler() {
                const mappingConfig = this.allConfigs['null'][this.configPath];

                Object.keys(mappingConfig).every((mappingKey) => {
                    if (!mappingConfig[mappingKey].customLabel) {
                        this.$set(this.mappingErrorStates[mappingKey], 'label', {
                            code: 1,
                            detail: this.$tc('klaviyo-integration-settings.customer.fieldMapping.labelNotFilledError'),
                        });
                    } else {
                        this.$delete(this.mappingErrorStates[mappingKey], 'label');
                    }

                    if (!mappingConfig[mappingKey].customFieldName) {
                        this.$set(this.mappingErrorStates[mappingKey], 'fieldCode', {
                            code: 1,
                            detail: this.$tc('klaviyo-integration-settings.customer.fieldMapping.mappingNotFilledError'),
                        });
                    } else {
                        this.$delete(this.mappingErrorStates[mappingKey], 'fieldCode');
                    }

                    return true;
                });
            },
            deep: true,
        },
    },

    methods: {
        createdComponent() {
            if (this.customFieldMapping === undefined || Array.isArray(this.customFieldMapping)) {
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
                    Object.keys(this.customFieldMapping).forEach((mappingKey) => {
                        this.$set(this.mappingErrorStates, mappingKey, {});

                        const allowedFiledNames = this.systemCustomFields.map((customField) => customField.name);

                        if (allowedFiledNames.indexOf(this.customFieldMapping[mappingKey].customFieldName) === -1) {
                            this.$set(this.mappingErrorStates[mappingKey], 'fieldCode', {
                                code: 1,
                                detail: this.$tc('klaviyo-integration-settings.customer.fieldMapping.mappingNotFilledError'),
                            });
                        }
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        onDeleteFieldMapping(mappingKey) {
            this.$delete(this.mappingErrorStates, mappingKey);
            this.$delete(this.customFieldMapping, mappingKey);
        },

        onAddNewFieldMapping() {
            const mappingKey = 'mapping_' + this.generateGuid();
            this.isAddingNewMappingState = true;
            this.$set(this.customFieldMapping, mappingKey, { customLabel: '', customFieldName: '' });
            this.$set(this.mappingErrorStates, mappingKey, {});
        },

        getMappingErrorState(mappingKey, type) {
            if (!this.mappingErrorStates[mappingKey] || this.mappingErrorStates[mappingKey][type]) {
                return null;
            }

            return this.mappingErrorStates[mappingKey][type];
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
