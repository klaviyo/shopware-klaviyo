import template from './od-job-listing-index.html.twig';
import './od-job-listing-index.scss';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('od-job-listing-index', {
    template,

    inject: [
        'OdRescheduleService',
        'repositoryFactory',
        'filterFactory',
        'feature'
    ],

    mixins: [
        'notification',
    ],

    props: {
        isGroupedView: {
            type: Boolean,
            required: false,
            default: false
        },
        jobTypes: {
            type: Array,
            required: false,
            default: () => []
        },
        filterCriteria: {
            type: Array,
            required: false,
            default: () => []
        }
    },

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
        }
    },

    watch: {
        autoLoadIsActive() {
            this._handleAutoReload(this.autoLoadIsActive);
        },

        jobDisplayType() {
            this.stopAutoLoading();
            this.$emit('job-display-type-changed', this.jobDisplayType);
        },

        filterCriteria() {
            this.filterCriteriaChanged(this.filterCriteria)
        }
    },

    computed: {
        jobRepository() {
            return this.repositoryFactory.create('od_scheduler_job');
        },

        messageRepository() {
            return this.repositoryFactory.create('od_scheduler_job_message');
        },

        columns() {
            return [
                {
                    property: 'name',
                    label: this.$tc('job-listing.page.listing.grid.column.name'),
                    allowResize: true,
                    width: '250px',
                },
                {
                    property: 'status',
                    label: this.$tc('job-listing.page.listing.grid.column.status'),
                    allowResize: true,
                    width: '150px',
                },
                {
                    property: 'startedAt',
                    label: this.$tc('job-listing.page.listing.grid.column.started-at'),
                    allowResize: true,
                    width: '170px',
                    sortable: true
                },
                {
                    property: 'finishedAt',
                    label: this.$tc('job-listing.page.listing.grid.column.finished-at'),
                    allowResize: true,
                    width: '170px',
                },
                {
                    property: 'createdAt',
                    label: this.$tc('job-listing.page.listing.grid.column.created-at'),
                    allowResize: true,
                    width: '170px',
                },
                {
                    property: 'subJobs',
                    label: 'Child Jobs',
                    allowResize: true,
                    width: '250px',
                    visible: true,
                    sortable: false,
                },
                {
                    property: 'messages',
                    label: 'Messages',
                    allowResize: true,
                    width: '250px',
                    visible: true,
                    sortable: false,
                }
            ];
        },

        jobDisplayMode() {
            return [
                {
                    name: 'List',
                    value: 'list'
                },
                {
                    name: 'Grouped',
                    value: 'grouped'
                },
                {
                    name: 'Chart',
                    value: 'chart'
                }
            ]
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.jobDisplayType = 'list';
            this.getList();
        },

        filterCriteriaChanged(criteria) {
            this.getList(criteria);
        },

        _handleAutoReload(active) {
            if (active && this.autoReloadInterval > 0) {
                if (this.jobDisplayType === 'list') {
                    this.reloadInterval = setInterval(() => {
                        this.updateList()
                    }, this.autoReloadInterval);
                } else if (this.jobDisplayType === 'grouped') {
                    this.reloadInterval = setInterval(() => {
                        this.$refs.jobGroups.initGroupedView()
                    }, this.autoReloadInterval);
                } else if (this.jobDisplayType === 'chart') {
                    this.reloadInterval = setInterval(() => {
                        this.$refs.jobCharts.initChartData()
                    }, this.autoReloadInterval);
                }
            } else {
                clearInterval(this.reloadInterval);
            }
        },

        pageChange() {
            this.autoLoadIsActive = false;
            clearInterval(this.reloadInterval);
        },

        getLinkParams(item) {
            return {
                id: item.id,
                backPath: this.$route.name
            };
        },

        updateList(filterCriteria) {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('parentId', null));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC', false));
            criteria.addAssociation('messages');
            criteria.addAssociation('subJobs');

            if (filterCriteria) {
                filterCriteria.forEach(filter => {
                    criteria.addFilter(filter);
                });
            }

            if (this.jobTypes !== []) {
                criteria.addFilter(Criteria.equalsAny('type', this.jobTypes));
            }

            return this.jobRepository.search(criteria, Shopware.Context.api).then(jobItems => {
                this.jobItems = jobItems;
            });
        },

        getMessagesCount(job, type) {
            return job.messages.filter(function (item) {
                return item.type === type + '-message';
            }).length;
        },

        getChildrenCount(job, type) {
            return job.subJobs.filter(function (item) {
                return item.status === type;
            }).length;
        },

        getList(filterCriteria) {
            this.isLoading = true;
            this.updateList(filterCriteria).then(() => {
                this.isLoading = false
            })
        },

        onRefresh(criteria) {
            if (this.jobDisplayType === 'grouped') {
                return this.$refs.jobGroups.onRefresh();
            } else if (this.jobDisplayType === 'chart') {
                return this.$refs.jobCharts.onRefresh();
            }
            return this.getList(criteria);
        },

        canDelete(item) {
            return ['error', 'succeed'].indexOf(item.status) !== -1;
        },

        onDeleteJob(jobId) {
            this.jobRepository.delete(jobId, Shopware.Context.api).then(() => {
                this.updateList();
            });
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
        },

        showSubJobs(jobId) {
            this.currentJobID = jobId;
            this.showJobSubsModal = true
        },

        showJobMessages(job) {
            this.currentJobMessages = job.messages;
            this.showMessagesModal = true
        },

        stopAutoLoading() {
            this.autoLoadIsActive = false;
            clearInterval(this.reloadInterval);

        }
    },

    beforeDestroy() {
        clearInterval(this.reloadInterval)
    },
});
