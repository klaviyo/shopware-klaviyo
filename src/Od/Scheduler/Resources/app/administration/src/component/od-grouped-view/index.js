import template from './od-grouped-view.html.twig';
import JobHelper from "../../util/job.helper";
import './od-grouped-view.scss';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('od-grouped-view', {
    template,

    inject: [
        'repositoryFactory'
    ],

    mixins: [
        'notification',
    ],

    props: {
        jobTypes: {
            type: Array,
            required: false,
            default: () => []
        },
        groupCreationDate: {
            type: Object,
            required: false,
            default: () => {}
        },
        sortType: {
            type: String,
            required: true,
            default: () => 'status'
        }
    },

    data() {
        return {
            groupedItems: [],
            isLoading: false,
            showJobInfoModal: false,
            showJobSubsModal: false,
            currentJobID: null,
            showMessagesModal: false,
            currentJobMessages: null,
        }
    },

    computed: {
        jobRepository() {
            return this.repositoryFactory.create('od_scheduler_job');
        },

        messageRepository() {
            return this.repositoryFactory.create('od_scheduler_job_message');
        },

        jobMessagesColumns() {
            return [
                {
                    property: 'message',
                    dataIndex: 'message',
                    label: this.$tc('job-listing.page.listing.grid.column.message'),
                    allowResize: false,
                    align: 'left',
                    width: '90px'
                }
            ]
        },

        columns() {
            return [
                {
                    property: 'name',
                    label: this.$tc('job-listing.page.listing.grid.column.name'),
                    allowResize: true,
                    width: '500px',
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
                    label: this.$tc('job-listing.page.listing.grid.column.child-jobs'),
                    allowResize: true,
                    width: '250px',
                    visible: true,
                    sortable: false,
                },
                {
                    property: 'messages',
                    label: this.$tc('job-listing.page.listing.grid.column.messages'),
                    allowResize: true,
                    width: '350px',
                    visible: true,
                    sortable: false,
                }
            ];
        },
    },

    created() {
        this.initGroupedView();
    },

    watch: {
        sortType() {
            this.groupedItems = [];
            this.initGroupedView();
        },
        groupCreationDate:{
            handler() {
                this.groupedItems = [];
                this.initGroupedView();
            },
            deep: true
        }
    },

    methods: {
        initGroupedView() {
            this.isLoading = true;
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('parentId', null));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC', false));
            criteria.addAssociation('messages');
            criteria.addAssociation('subJobs');
            criteria.limit = null;

            if (this.jobTypes !== []) {
                criteria.addFilter(Criteria.equalsAny('type', this.jobTypes));
            }

            if(this.groupCreationDate.fromDate){
                criteria.addFilter(Criteria.range('createdAt', { gte: this.groupCreationDate.fromDate }));
            }

            if(this.groupCreationDate.toDate){
                criteria.addFilter(Criteria.range('createdAt', { lte: this.groupCreationDate.toDate }));
            }

            return this.jobRepository.search(criteria, Shopware.Context.api).then(items => {
                this.sortJobs(items)
            });
        },

        sortJobs(items) {
            this.groupedItemsTypes = [];
            this.groupedItems = [];
            items.forEach((item) => {
                let index = this.groupedItemsTypes.findIndex(e => e.title === item[this.sortType])
                if (index === -1) {
                    this.groupedItemsTypes.push({
                        title: item[this.sortType]
                    })
                }
            })

            this.getJobsByType(this.groupedItemsTypes);
        },

        getJobsByType(types) {
            types.forEach((type) => {
                const criteria = new Criteria();
                criteria.addFilter(Criteria.equals('parentId', null));
                criteria.addSorting(Criteria.sort('createdAt', 'DESC', false));
                criteria.addAssociation('messages');
                criteria.addAssociation('subJobs');

                if(this.groupCreationDate.fromDate){
                    criteria.addFilter(Criteria.range('createdAt', { gte: this.groupCreationDate.fromDate }));
                }

                if(this.groupCreationDate.toDate){
                    criteria.addFilter(Criteria.range('createdAt', { lte: this.groupCreationDate.toDate }));
                }

                if (this.jobTypes !== []) {
                    criteria.addFilter(Criteria.equalsAny('type', this.jobTypes));
                }

                if (this.sortType === 'status') {
                    criteria.addFilter(Criteria.equals('status', type.title));
                }

                if (this.sortType === 'type') {
                    criteria.addFilter(Criteria.equals('type', type.title));
                }

                this.jobRepository.search(criteria, Shopware.Context.api).then(items => {
                    const groupType = this.sortType === 'status' ? type.title.toUpperCase() : items[0].name;
                    const groupTitle = this.sortType === 'status'
                        ? this.$tc('job-listing.page.listing.grid.job-status.' + type.title)
                        : items[0].name;
                    this.groupedItems.push({
                        title: groupTitle,
                        type: groupType,
                        items: JobHelper.sortMessages(items)
                    });
                });
            })

            this.isLoading = false;

            return this.groupedItems;
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

        onRefresh() {
            this.initGroupedView();
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

        showJobInfo(jobId) {
            this.currentJobID = jobId;
            this.showJobInfoModal = true
        },

        showSubJobs(jobId) {
            this.currentJobID = jobId;
            this.showJobSubsModal = true
        },

        showJobMessages(job) {
            this.currentJobMessages = job.messages;
            this.showMessagesModal = true
        }
    },

    beforeDestroy() {
        clearInterval(this.reloadInterval)
    },
});
