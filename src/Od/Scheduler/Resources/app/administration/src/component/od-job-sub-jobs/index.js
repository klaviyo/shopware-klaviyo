import template from './od-job-sub-jobs.html.twig';
import JobHelper from "../../util/job.helper";
import './od-job-sub-jobs.scss';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('od-job-sub-jobs', {
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

    data() {
        return {
            subJobs: null,
            showMessagesModal: false,
            currentJobMessages: null
        }
    },

    computed: {
        jobRepository() {
            return this.repositoryFactory.create('od_scheduler_job');
        },

        jobChildrenColumns() {
            return [
                {
                    property: 'name',
                    dataIndex: 'name',
                    label: this.$tc('job-listing.page.listing.grid.column.name'),
                    allowResize: false,
                    inlineEdit: true,
                    width: '200px'
                },
                {
                    property: 'status',
                    dataIndex: 'status',
                    label: this.$tc('job-listing.page.listing.grid.column.status'),
                    allowResize: false,
                    inlineEdit: true,
                    width: '100px'
                },
                {
                    property: 'startedAt',
                    dataIndex: 'startedAt',
                    label: this.$tc('job-listing.page.listing.grid.column.started-at'),
                    allowResize: false,
                    inlineEdit: true,
                    width: '150px',
                    sortable: true
                },
                {
                    property: 'finishedAt',
                    dataIndex: 'finishedAt',
                    label: this.$tc('job-listing.page.listing.grid.column.finished-at'),
                    allowResize: true,
                    inlineEdit: true,
                    width: '150px'
                },
                {
                    property: 'createdAt',
                    dataIndex: 'createdAt',
                    label: this.$tc('job-listing.page.listing.grid.column.created-at'),
                    allowResize: true,
                    inlineEdit: true,
                    width: '150px'
                },
                {
                    property: 'messages',
                    dataIndex: 'messages',
                    label: 'Messages',
                    allowResize: true,
                    inlineEdit: false,
                    width: '250px',
                    sortable: false
                },
            ];
        },
    },

    created() {
        this.initModalData();
    },

    methods: {
        initModalData() {
            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('parentId', this.jobId));
            criteria.addSorting(Criteria.sort('createdAt', 'DESC', false));
            criteria.addAssociation('messages');
            this.jobRepository.search(criteria, Shopware.Context.api).then(jobItems => {
                this.subJobs = JobHelper.sortMessages(jobItems);
            });
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

        showMessageModal(messages) {
            this.currentJobMessages = messages;
            this.showMessagesModal = true;
        },

        getMessagesCount(job, type) {
            return job.messages.filter(function (item) {
                return item.type === type + '-message';
            }).length;
        },
    }
});
