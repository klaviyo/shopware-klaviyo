import { reactive } from 'vue';
import template from './od-entity-listing.html.twig';
import './od-entity-listing.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.extend('od-entity-listing', 'sw-entity-listing', {
    template,

    props: {
        items: {
            type: Array,
            required: true
        },

        itemIdentifierProperty: {
            type: String,
            required: false,
            default: 'id'
        },

        preSelection: {
            type: Object,
            required: false,
            default: null
        },

        isGroupedView: {
            type: Boolean,
            required: false,
            default: false
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
            records: this.items,
            selection: reactive(this.preSelection ? Object.assign({}, this.preSelection) : {}),
            successItems: false,
            pendingItems: false,
            errorItems: false,
            reloadInterval: null,
            page: 1,
            limit: 25
        };
    },

    watch: {
        items(newItems) {
            this.records = newItems;
        }
    },

    methods: {
        canDelete(item) {
            return ['error', 'succeed'].includes(item.status);
        },

        selectAll(selected) {
            // Clear selection
            Object.keys(this.selection).forEach(key => {
                delete this.selection[key];
            });

            // Select/deselect all items
            if (selected) {
                this.records.forEach(item => {
                    if (this.canDelete(item)) {
                        this.selection[item[this.itemIdentifierProperty]] = item;
                    }
                });
            }

            this.$emit('select-all-items', this.selection);
        },

        selectItem(selected, item) {
            if (!this.canDelete(item)) {
                return;
            }

            const key = item[this.itemIdentifierProperty];
            if (selected) {
                this.selection[key] = item;
            } else {
                delete this.selection[key];
            }

            this.$emit('select-item', this.selection, item, selected);
        }
    }
});