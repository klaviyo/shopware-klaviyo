import template from './klaviyo-job-listing.html.twig';
import './klaviyo-job-listing.scss';

const {Component, Mixin} = Shopware;
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
                'od-klaviyo-subscriber-sync-handler'
            ];
        },

        listFilters() {
            return this.filterFactory.create('od_scheduler_job', {
                'job-status-filter': {
                    property: 'status',
                    type: 'multi-select-filter',
                    label: 'Job Status',
                    placeholder: 'Select status...',
                    valueProperty: 'value',
                    labelProperty: 'name',
                    options: this.statusFilterOptions
                },
                'job-type-filter': {
                    property: 'name',
                    type: 'multi-select-filter',
                    label: 'Job Type',
                    placeholder: 'Select type...',
                    valueProperty: 'value',
                    labelProperty: 'name',
                    options: this.typeFilterOptions
                },
                'job-date-filter': {
                    property: 'createdAt',
                    label: 'Job Created At',
                    dateType: 'datetime-local',
                    fromFieldLabel: 'From',
                    toFieldLabel: 'To',
                    showTimeframe: true,
                },
            });
        }
    },

    created() {
        this.createdComponent()
    },

    methods: {
        createdComponent() {
            this.loadFilterValues();
        },

        onDisplayModeChange(mode) {
            console.log("fil", this.$refs.odSidebar)
            let innerBox = this.$el;

                innerBox.classList.remove('no-filter');
            if (mode !== 'list') {
                innerBox.classList.add('no-filter');
                this.$refs.odSidebar.closeSidebar();

                if(this.$refs.odFilter.$el.length !== 0){
                    this.$refs.odFilter.resetAll();
                }

                console.log(this.$el.querySelector('.sw-sidebar ').classList.contains('is--opened'));
                return this.hideFilters = true
            }


            this.hideFilters = false;
            this.loadFilterValues();
        },

        onRefresh() {
            this.$refs.jobListing.onRefresh(this.filterCriteria);
            this.loadFilterValues();
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
            criteria.addAssociation('messages');
            criteria.addAssociation('subJobs');

            criteria.addFilter(Criteria.equalsAny('type', [
                'od-klaviyo-events-sync-handler',
                'od-klaviyo-cart-event-sync-handler',
                'od-klaviyo-full-order-sync-handler',
                'od-klaviyo-full-subscriber-sync-handler',
                'od-klaviyo-order-event-sync-handler',
                'od-klaviyo-order-sync-handler',
                'od-klaviyo-subscriber-sync-handler'
            ]));

            return this.jobRepository.search(criteria, Shopware.Context.api).then((items) => {
                const statuses = [...new Set(items.map(item => item.status))];
                const types = [...new Set(items.map(item => item.name))];

                this.statusFilterOptions = [];
                this.typeFilterOptions = [];

                statuses.forEach((status) => {
                    this.statusFilterOptions.push({
                        name: status === 'succeed' ? 'Success' : status,
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

                return Promise.resolve();
            }).catch(() => {
                this.filterLoading = false;
            });
        },
    }
});
