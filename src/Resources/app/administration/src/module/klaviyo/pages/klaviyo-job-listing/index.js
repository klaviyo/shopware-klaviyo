import template from './klaviyo-job-listing.html.twig';
import './klaviyo-job-listing.scss';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('klaviyo-job-listing', {
    template,

    inject: [
        'OdRescheduleService',
        'repositoryFactory',
        'filterFactory',
        'feature'
    ],

    data() {
        return {
            jobItems: null,
            isLoading: false,
            reloadInterval: null,
            showJobInfoModal: false,
            showJobSubsModal: false,
            currentJobID: null,
            showMessagesModal: false,
            currentJobMessages: null,
            sortType: 'status',
            jobDisplayType: null,
            autoLoad: false,
            autoLoadIsActive: false,
            autoReloadInterval: 60000,
            statusFilterOptions: [],
            typeFilterOptions: [],
            filterCriteria: [],
            defaultFilters: [
                'job-status-filter',
                'job-type-filter',
                'job-date-filter'
            ],
            storeKey: 'klaviyo_filters',
            activeFilterNumber: 0,
            searchConfigEntity: 'od_scheduler_job',
            showBulkEditModal: false,
            hideFilters: false
        }
    },

    computed: {
        jobRepository() {
            return this.repositoryFactory.create('od_scheduler_job');
        },

        klaviyoJobTypes() {
            return [
                'od-klaviyo-events-sync-handler',
                'od-klaviyo-cart-event-sync-handler',
                'od-klaviyo-full-order-sync-handler',
                'od-klaviyo-full-subscriber-sync-handler',
                'od-klaviyo-order-event-sync-handler',
                'od-klaviyo-order-sync-handler',
                'od-klaviyo-subscriber-sync-handler',
                'od-klaviyo-daily-excluded-subscriber-sync-handler'
            ];
        },

        listFilters() {
            return this.filterFactory.create('od_scheduler_job', {
                'job-status-filter': {
                    property: 'status',
                    type: 'multi-select-filter',
                    label: this.$tc('klaviyo-job-listing.page.job-listing.filter.job-status'),
                    placeholder: this.$tc('klaviyo-job-listing.page.job-listing.filter.job-status-placeholder'),
                    valueProperty: 'value',
                    labelProperty: 'name',
                    options: this.statusFilterOptions
                },
                'job-type-filter': {
                    property: 'name',
                    type: 'multi-select-filter',
                    label: this.$tc('klaviyo-job-listing.page.job-listing.filter.job-type'),
                    placeholder: this.$tc('klaviyo-job-listing.page.job-listing.filter.job-type-placeholder'),
                    valueProperty: 'value',
                    labelProperty: 'name',
                    options: this.typeFilterOptions
                },
                'job-date-filter': {
                    property: 'createdAt',
                    label: this.$tc('klaviyo-job-listing.page.job-listing.filter.created-at'),
                    dateType: 'datetime-local',
                    fromFieldLabel: this.$tc('klaviyo-job-listing.page.job-listing.filter.from'),
                    toFieldLabel: this.$tc('klaviyo-job-listing.page.job-listing.filter.to'),
                    showTimeframe: true,
                },
            });
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            return this.loadFilterValues();
        },

        onDisplayModeChange(mode) {
            let innerBox = this.$el;
            innerBox.classList.remove('no-filter');

            if (mode !== 'list') {
                innerBox.classList.add('no-filter');
                this.$refs.odSidebar.closeSidebar();

                if (this.$refs.odFilter.$el.length !== 0) {
                    this.$refs.odFilter.resetAll();
                }

                return this.hideFilters = true
            }

            this.hideFilters = false;
        },

        onRefresh() {
            this.$refs.jobListing.onRefresh(this.filterCriteria);
        },

        updateCriteria(criteria) {
            this.page = 1;
            this.filterCriteria = criteria;
            this.activeFilterNumber = criteria.length;
        },

        loadFilterValues() {
            this.filterLoading = true;

            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('parentId', null));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC', false));
            criteria.setLimit(999999);
            criteria.addFilter(Criteria.equalsAny('type', [
                'od-klaviyo-events-sync-handler',
                'od-klaviyo-cart-event-sync-handler',
                'od-klaviyo-full-order-sync-handler',
                'od-klaviyo-full-subscriber-sync-handler',
                'od-klaviyo-order-event-sync-handler',
                'od-klaviyo-order-sync-handler',
                'od-klaviyo-subscriber-sync-handler',
                'od-klaviyo-daily-excluded-subscriber-sync-handler'
            ]));

            return this.jobRepository.search(criteria, Shopware.Context.api).then((items) => {
                const statuses = [...new Set(items.map(item => item.status))];
                const types = [...new Set(items.map(item => item.name))];

                this.statusFilterOptions = [];
                this.typeFilterOptions = [];

                statuses.forEach((status) => {
                    this.statusFilterOptions.push({
                        name: this.$tc('job-listing.page.listing.grid.job-status.' + status),
                        value: status
                    })
                })

                types.forEach((type) => {
                    this.typeFilterOptions.push({
                        name: type,
                        value: type
                    })
                })

                this.filterLoading = false;

                return Promise.resolve([]);
            }).catch(() => {
                this.filterLoading = false;
            });
        },
    }
});
