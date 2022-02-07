import template from './od-job-status-badge.html.twig';

const {Component} = Shopware;

Component.register('od-job-status-badge', {
    template,

    props: {
        status: {
            type: String,
            required: true,
        }
    },

    computed: {
        additionalClass() {
            return this.status === 'running' ? '--pulse' : '';
        },

        variant() {
            switch (this.status) {
                case 'error':
                    return 'error';
                case 'succeed':
                case 'running':
                    return 'success';
                default:
                    return '';
            }
        },
    },
});
