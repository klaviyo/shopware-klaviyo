import template from './klaviyo-integration-settings-customer.html.twig';
import './klaviyo-integration-settings-customer.scss';

const {Component, Context, Mixin} = Shopware;
const {Criteria} = Shopware.Data;

// This component is destroyed/recreated on every visit to the Mapping tab (v-if-gated), so without
// caching, switching tabs back and forth would re-fire this Admin API lookup every time. Cached at
// module scope (not per-instance) so it survives across mounts for the lifetime of the page
let cachedCustomerCustomFieldsPromise = null;

function fetchSystemCustomFields(customFieldRepository, customFieldCriteria) {
    if (!cachedCustomerCustomFieldsPromise) {
        cachedCustomerCustomFieldsPromise = customFieldRepository
            .search(customFieldCriteria, Context.api)
            .catch((error) => {
                cachedCustomerCustomFieldsPromise = null;
                throw error;
            });
    }

    return cachedCustomerCustomFieldsPromise;
}

Component.register('klaviyo-integration-settings-customer', {
    template,

    mixins: [Mixin.getByName('notification')],

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
            localeIso: null,
            configPath: 'klavi_overd.config.customerFieldMapping'
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
            if (this.systemCustomFields?.total === 0) {
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

            fetchSystemCustomFields(this.customFieldRepository, this.customFieldCriteria)
                .then((customFields) => {
                    this.systemCustomFields = customFields;
                    this.processCustomFieldMappings();
                })
                .catch(() => {
                    // Without the system field list we cannot prune, so leave the stored mappings untouched
                    this.createNotificationError({
                        message: this.$tc('klaviyo-integration-settings.customer.fieldMapping.loadFieldsError'),
                    });
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        processCustomFieldMappings() {
            if (!this.systemCustomFields) {
                return;
            }

            let existingCustomFieldNames = [];
            const systemFieldNames = (this.systemCustomFields ?? []).map((systemField) => systemField.name);

            Object.keys(this.customFieldMapping).forEach(mappingKey => {
                existingCustomFieldNames.push(this.customFieldMapping[mappingKey].customFieldName);

                if (systemFieldNames.indexOf(this.customFieldMapping[mappingKey]['customFieldName']) === -1) {
                    this.$delete(this.customFieldMapping, mappingKey);
                }
            });

            systemFieldNames.forEach((systemFieldName) => {
                if (!existingCustomFieldNames.includes(systemFieldName)) {
                    const systemField = this.systemCustomFields.find((field) => field.name === systemFieldName);
                    if (systemField) {
                        this.addNewEmptyFieldMapping(systemField);
                    }
                }
            });
        },

        getCustomFieldHint(mappingKey) {
            const systemFieldName = this.customFieldMapping[mappingKey]?.customFieldName;
            const systemField = (this.systemCustomFields ?? []).find((field) => field.name === systemFieldName) ?? {};

            return systemField?.config?.label[this.localeIso] ?? systemField?.name ?? '<not_found>';
        },

        addNewEmptyFieldMapping(field) {
            const mappingKey = 'mapping_' + this.generateGuid();
            this.$set(this.customFieldMapping, mappingKey, {
                customLabel: '',
                customFieldName: field.name,
                active: false
            });
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
