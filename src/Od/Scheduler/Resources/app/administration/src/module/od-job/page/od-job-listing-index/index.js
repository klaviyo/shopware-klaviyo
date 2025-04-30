import template from './od-job-listing-index.html.twig';
import JobHelper from "../../../../util/job.helper";
import './od-job-listing-index.scss';

const { Component, Mixin, Context } = Shopware;
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
        Mixin.getByName('notification')
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
            currentSortBy: 'startedAt',
            sortDirection: 'DESC',
            isLoading: false,
            reloadInterval: null,
            showJobInfoModal: false,
            showJobSubsModal: false,
            currentJobID: null,
            showMessagesModal: false,
            currentJobMessages: null,
            total: 0,
            groupCreationDate: {},
            sortType: 'status',
            jobDisplayType: null,
            autoLoad: false,
            autoLoadIsActive: false,
            autoReloadInterval: 60000,
            page: 1,
            limit: 25
        };
    },

    watch: {
        autoLoadIsActive(value) {
            this._handleAutoReload(value);
        },

        jobDisplayType(value) {
            this.stopAutoLoading();
            this.$emit('job-display-type-changed', value);
        },

        filterCriteria: {
            handler(criteria) {
                this.filterCriteriaChanged(criteria);
            },
            deep: true
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
                    width: '250px'
                },
                {
                    property: 'status',
                    label: this.$tc('job-listing.page.listing.grid.column.status'),
                    allowResize: true,
                    width: '150px'
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
                    sortable: true
                },
                {
                    property: 'createdAt',
                    label: this.$tc('job-listing.page.listing.grid.column.created-at'),
                    allowResize: true,
                    width: '170px',
                    sortable: true
                },
                {
                    property: 'subJobs',
                    label: this.$tc('job-listing.page.listing.grid.column.child-jobs'),
                    allowResize: true,
                    width: '250px',
                    visible: true,
                    sortable: false
                },
                {
                    property: 'messages',
                    label: this.$tc('job-listing.page.listing.grid.column.messages'),
                    allowResize: true,
                    width: '250px',
                    visible: true,
                    sortable: false
                }
            ];
        },

        jobDisplayMode() {
            return [
                {
                    label: this.$tc('job-listing.page.listing.index.list'),
                    value: 'list'
                },
                {
                    label: this.$tc('job-listing.page.listing.index.grouped'),
                    value: 'grouped'
                },
                {
                    label: this.$tc('job-listing.page.listing.index.chart'),
                    value: 'chart'
                }
            ];
        },

        displayTypesOptions() {
            return [
                {
                    label: this.$tc('job-listing.page.listing.index.status'),
                    value: 'status'
                },
                {
                    label: this.$tc('job-listing.page.listing.index.job-type'),
                    value: 'type'
                }
            ];
        }
    },

    created() {
        this.createdComponent();
    },

    beforeUnmount() {
        clearInterval(this.reloadInterval);
    },

    methods: {
        paginate({ page, limit }) {
            this.page = page;
            this.limit = limit;
            this.getList();
        },

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
                        this.updateList();
                    }, this.autoReloadInterval);
                } else if (this.jobDisplayType === 'grouped') {
                    this.reloadInterval = setInterval(() => {
                        this.$refs.jobGroups?.initGroupedView();
                    }, this.autoReloadInterval);
                } else if (this.jobDisplayType === 'chart') {
                    this.reloadInterval = setInterval(() => {
                        this.$refs.jobCharts?.initChartData();
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
            const criteria = new Criteria(this.page, this.limit);
            criteria.addFilter(Criteria.equals('parentId', null));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC', false));
            criteria.addAssociation('messages');
            criteria.addAssociation('subJobs');

            if (filterCriteria) {
                filterCriteria.forEach(filter => {
                    criteria.addFilter(filter);
                });
            }

            if (this.jobTypes.length > 0) {
                criteria.addFilter(Criteria.equalsAny('type', this.jobTypes));
            }

            return this.jobRepository.search(criteria, Context.api).then(jobItems => {
                this.jobItems = JobHelper.sortMessages(jobItems);
                this.total = jobItems.total;
            });
        },

        getMessagesCount(job, type) {
            return job.messages.filter(item => item.type === `${type}-message`).length;
        },

        getChildrenCount(job, type) {
            return job.subJobs.filter(item => item.status === type).length;
        },

        getList(filterCriteria) {
            this.isLoading = true;
            return this.updateList(filterCriteria).then(() => {
                this.isLoading = false;
            }).catch(error => {
                this.isLoading = false;
            });
        },

        onRefresh(criteria) {
            if (this.jobDisplayType === 'grouped') {
                return this.$refs.jobGroups?.onRefresh();
            } else if (this.jobDisplayType === 'chart') {
                return this.$refs.jobCharts?.onRefresh();
            }
            return this.getList(criteria);
        },

        canDelete(item) {
            return ['error', 'succeed'].includes(item.status);
        },

        onDeleteJob(jobId) {
            this.jobRepository.delete(jobId, Context.api).then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('job-listing.page.listing.deleteSuccess')
                });
                this.updateList();
            }).catch(error => {
                this.createNotificationError({
                    message: this.$tc('job-listing.page.listing.deleteError')
                });
            });
        },

        rescheduleJob(jobId) {
            this.OdRescheduleService.rescheduleJob(jobId).then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('job-listing.page.listing.rescheduleSuccess')
                });
                this.updateList();
            }).catch(error => {
                this.createNotificationError({
                    message: this.$tc('job-listing.page.listing.rescheduleError')
                });
            });
        },

        showSubJobs(jobId) {
            this.currentJobID = jobId;
            this.showJobSubsModal = true;
        },

        showJobMessages(job) {
            this.currentJobMessages = job.messages;
            this.showMessagesModal = true;
        },

        stopAutoLoading() {
            this.autoLoadIsActive = false;
            clearInterval(this.reloadInterval);
        }
    }
});