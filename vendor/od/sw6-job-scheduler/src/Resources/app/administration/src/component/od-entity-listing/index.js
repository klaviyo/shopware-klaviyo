import template from './od-entity-listing.html.twig'
import './od-entity-listing.scss';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.extend('od-entity-listing', 'sw-entity-listing', {
    template,

    props: {
        items: {
            type: Array,
            required: true,
        },

        itemIdentifierProperty: {
            type: String,
            required: false,
            default: 'id',
        },

        preSelection: {
            type: Object,
            required: false,
            default: null,
        },

        isGroupedView: {
            type: Boolean,
            required: false,
            default: false,
        },

        jobTypes: {
            type: Array,
            required: false,
            default: () => []
        }
    },

    computed: {
        jobRepository() {
            return this.repositoryFactory.create('od_scheduler_job');
        }
    },

    data() {
        return {
            /** @type {Array} */
            records: this.items,
            selection: Object.assign({}, this.preSelection || {}),
            successItems: false,
            pendingItems: false,
            errorItems: false,
            reloadInterval: null,
            page: 1,
            limit: 25
        };
    },

    methods: {
        canDelete(item) {
            return ['error', 'succeed'].indexOf(item.status) !== -1;
        },

        selectAll(selected) {
            this.$delete(this.selection);
            this.records.forEach(item => {
                if (this.isSelected(item[this.itemIdentifierProperty]) !== selected) {
                    this.selectItem(selected, item);
                }
            });

            this.$emit('select-all-items', this.selection);
        },

        selectItem(selected, item) {
            if (!this.canDelete(item)) {
                return;
            }

            const selection = this.selection;

            if (selected) {
                this.$set(this.selection, item[this.itemIdentifierProperty], item);
            } else if (!selected && selection[item[this.itemIdentifierProperty]]) {
                this.$delete(this.selection, item[this.itemIdentifierProperty]);
            }

            this.$emit('select-item', this.selection, item, selected);
        },
    },
});
