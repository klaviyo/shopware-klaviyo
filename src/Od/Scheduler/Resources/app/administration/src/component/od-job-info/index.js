import template from './od-job-info.html.twig';

const {Component} = Shopware;
const {Criteria} = Shopware.Data;

Component.register('od-job-info', {
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
            jobItem: null
        }
    },

    computed: {
        jobRepository() {
            return this.repositoryFactory.create('od_scheduler_job');
        }
    },

    created() {
        this.initPageData();
    },

    methods: {
        initPageData() {
            this.jobRepository.get(this.jobId, Shopware.Context.api, new Criteria()).then(jobItem => {
                this.jobItem = jobItem;
            });
        },
    }
});
