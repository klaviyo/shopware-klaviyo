import template from './od-job-detail-index.html.twig';
import './od-job-detail-index.scss';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('od-job-detail-index', {
    template,

    inject: [
        'OdRescheduleService',
        'repositoryFactory'
    ],

    mixins: [
        'notification',
    ],

    props: {
        jobId: {
            type: String,
            required: false,
            default: null,
        }
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data: function () {
        return {
            parentRoute: null,
            jobItem: null,
            jobChildren: null,
            jobMessages: null,
            currentJobMessages: null,
            displayedLog: null,
            moduleData: this.$route.meta.$module,
        }
    },

    computed: {
        jobRepository() {
            return this.repositoryFactory.create('od_scheduler_job');
        },
        jobMessagesRepository() {
            return this.repositoryFactory.create('od_scheduler_job_message');
        },
        jobChildrenColumns() {
            return this.getJobChildrenColumns();
        },
        jobMessagesColumns() {
            return this.getJobMessagesColumns();
        },

        backPath() {
            if (this.$route.params.backPath === undefined) {
                return null;
            }

            return { name: this.$route.params.backPath };
        },
    },

    created() {
        this.initPageData();
    },

    mounted() {
        if (this.$route.params.parentPath) {
            this.parentRoute = this.$route.params.parentPath;
        }
    },

    methods: {
        initPageData() {
            this.jobRepository.get(this.jobId, Shopware.Context.api, new Criteria()).then(jobItem => {
                this.jobItem = jobItem;
            }).then(() => {
                this.getJobChildren().then(() => {
                    this.getJobMessages();
                });
            })
        },

        getJobChildren() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equalsAny('parentId', [this.jobId]));
            criteria.addSorting(Criteria.sort('createdAt', 'ASC', false));

            return this.jobRepository.search(criteria, Shopware.Context.api).then(jobChildren => {
                this.jobChildren = jobChildren;
            });
        },

        getJobChildrenColumns() {
            return [
                {
                    property: 'name',
                    dataIndex: 'name',
                    label: this.$tc('job-listing.page.listing.grid.column.name'),
                    allowResize: false,
                    align: 'right',
                    inlineEdit: true,
                    width: '90px'
                },
                {
                    property: 'status',
                    dataIndex: 'status',
                    label: this.$tc('job-listing.page.listing.grid.column.status'),
                    allowResize: false,
                    align: 'right',
                    inlineEdit: true,
                    width: '90px'
                },
                {
                    property: 'startedAt',
                    dataIndex: 'startedAt',
                    label: this.$tc('job-listing.page.listing.grid.column.started-at'),
                    allowResize: false,
                    align: 'right',
                    inlineEdit: true,
                    width: '90px'
                },
                {
                    property: 'finishedAt',
                    dataIndex: 'finishedAt',
                    label: this.$tc('job-listing.page.listing.grid.column.finished-at'),
                    allowResize: true,
                    align: 'right',
                    inlineEdit: true,
                    width: '90px'
                },
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: this.$tc('job-listing.page.listing.grid.column.created-at'),
                    allowResize: true,
                    align: 'right',
                    inlineEdit: true,
                    width: '90px'
                },
            ];
        },

        getJobMessages() {
            const criteria = new Criteria();
            var jobIds = this.jobChildren ? this.jobChildren.map((job) => job.id) : [];
            criteria.addFilter(Criteria.equalsAny('jobId', jobIds.concat([this.jobId])));
            criteria.addSorting(Criteria.sort('createdAt', 'ASC', false));

            return this.jobMessagesRepository.search(criteria, Shopware.Context.api).then(jobMessages => {
                this.jobMessages = jobMessages;
            });
        },

        getJobMessagesColumns() {
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

        canReschedule(item) {
            return item.status === 'error';
        },

        canShowJobMessages(jobId) {
            return Object.values(this.jobMessages).filter((message) => message.jobId === jobId).length > 0;
        },

        rescheduleJob(jobId) {
            this.OdRescheduleService.rescheduleJob(jobId).then(() => {
                this.createNotificationSuccess({
                    message: "Job has been rescheduled successfully.",
                });
                this.initPageData();
            }).catch(() => {
                this.createNotificationError({
                    message: "Unable reschedule job.",
                });
            })
        },

        showMessageModal(jobId) {
            this.currentJobMessages = Object.values(this.jobMessages).filter((message) => message.jobId === jobId);
            this.displayedLog = true;
        },

        closeModal() {
            this.displayedLog = false;
        },
    }
});
