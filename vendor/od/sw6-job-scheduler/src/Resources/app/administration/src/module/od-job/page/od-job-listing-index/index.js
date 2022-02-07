import template from './od-job-listing-index.html.twig';
import './od-job-listing-index.scss';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('od-job-listing-index', {
    template,

    inject: [
        'OdRescheduleService',
        'repositoryFactory',
    ],

    mixins: [
        'notification',
    ],

    props: {
        autoReloadInterval: {
            type: Number,
            required: false,
            default: () => 0
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
        },

        columns() {
            return [
                {
                    property: 'name',
                    label: this.$tc('job-listing.page.listing.grid.column.name'),
                    allowResize: true
                },
                {
                    property: 'status',
                    label: this.$tc('job-listing.page.listing.grid.column.status'),
                    allowResize: true
                },
                {
                    property: 'startedAt',
                    label: this.$tc('job-listing.page.listing.grid.column.started-at'),
                    allowResize: true
                },
                {
                    property: 'finishedAt',
                    label: this.$tc('job-listing.page.listing.grid.column.finished-at'),
                    allowResize: true
                },
                {
                    property: 'createdAt',
                    label: this.$tc('job-listing.page.listing.grid.column.created-at'),
                    allowResize: true
                },
            ];
        },
    },

    created() {
        this.createdComponent();
    },

    data: function () {
        return {
            jobItems: null,
            isLoading: false
        }
    },

    methods: {
        createdComponent() {
            this.getList();

            if (this.autoReloadInterval > 0) {
                setInterval(() => {this.updateList()}, this.autoReloadInterval)
            }
        },

        getLinkParams(item) {
            return {
                id: item.id,
                backPath: this.$route.name
            };
        },

        updateList() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('parentId', null));
            criteria.addSorting(Criteria.sort('createdAt', 'ASC', false));

            if (this.jobTypes !== []) {
                criteria.addFilter(Criteria.equalsAny('type', this.jobTypes));
            }

            return this.jobRepository.search(criteria, Shopware.Context.api).then(jobItems => {
                this.jobItems = jobItems;
            });
        },

        getList() {
            this.isLoading = true;
            this.updateList().then(() => {this.isLoading = false})
        },

        onRefresh() {
            this.getList();
        },

        canReschedule(item) {
            return item.status === 'error';
        },

        rescheduleJob(jobId) {
            this.OdRescheduleService.rescheduleJob(jobId).then(() => {
                this.createNotificationSuccess({
                    message: "Job has been rescheduled successfully.",
                });
                this.updateList();
            }).catch(() => {
                this.createNotificationError({
                    message: "Unable reschedule job.",
                });
            })
        }
    }
});
