import enGB from './snippet/en-GB.json';
import template from './template.html.twig';
import './styles.scss';
import JobInteractor from './job-interactor';

const {Component, Mixin} = Shopware;
const {date} = Shopware.Utils.format;

Component.register('klaviyo-historical-events-synchronization-control', {
    template,

    inject: [
        'klaviyoHistoricalEventsSynchronizationApiService',
        'klaviyoSubscribersSynchronizationApiService',
    ],

    snippets: {
        'en-GB': enGB,
    },

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        const historicalEventsJobInteractor = new JobInteractor(
            this.klaviyoHistoricalEventsSynchronizationApiService,
            date
        );
        const subscribersSynchronizationJobInteractor = new JobInteractor(
            this.klaviyoSubscribersSynchronizationApiService,
            date
        );

        return {
            historicalEventsJobInteractor: historicalEventsJobInteractor,
            subscribersSynchronizationJobInteractor: subscribersSynchronizationJobInteractor,
        }
    },

    created() {
        this.createdComponent();
    },

    computed: {
        scheduleHistoricalEventsSynchronizationTooltip() {
            return this.getSynchronizationTooltipOptions(
                this.historicalEventsJobInteractor,
                'klaviyo_integration_plugin.historical_events_tracking.last_synchronization_status',
                'klaviyo_integration_plugin.historical_events_tracking.last_success_synchronization'
            );
        },
        scheduleSubscribersSynchronizationTooltip() {
            return this.getSynchronizationTooltipOptions(
                this.subscribersSynchronizationJobInteractor,
                'klaviyo_integration_plugin.subscribers.last_synchronization_status',
                'klaviyo_integration_plugin.subscribers.last_success_synchronization'
            );
        }
    },

    methods: {
        createdComponent() {
            this.updateJobStatuses();
        },

        updateJobStatuses() {
            this.updateHistoricalEventJobStatus();
            this.updateSubscribersSyncJobStatus();
        },

        updateHistoricalEventJobStatus() {
            this.historicalEventsJobInteractor.updateJobStatuses().catch(
                () => {
                    this.createNotificationError({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.get_synchronization_status.failed'
                        )
                    });
                }
            );
        },

        updateSubscribersSyncJobStatus() {
            this.subscribersSynchronizationJobInteractor.updateJobStatuses().catch(
                () => {
                    this.createNotificationError({
                        message: this.$tc(
                            'klaviyo_integration_plugin.schedule_synchronization.get_synchronization_status.failed'
                        )
                    });
                }
            );
        },

        scheduleHistoricalEventsSynchronization() {
            const promise = this.historicalEventsJobInteractor.scheduleSynchronization();
            promise.then(function (response) {
                if (response.data.isScheduled) {
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.success'
                        )
                    });
                } else if (response.data.errorCode === 'SYNCHRONIZATION_IS_ALREADY_RUNNING') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.is_running'
                        )
                    });
                } else {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.failed'
                        )
                    });
                }
            }.bind(this)).catch(function (error) {
                this.createNotificationError({
                    message: this.$tc(
                        'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.failed'
                    )
                });
            }.bind(this));
        },
        scheduleSubscribersSynchronization() {
            const promise = this.subscribersSynchronizationJobInteractor.scheduleSynchronization();

            promise.then(function (response) {
                if (response.data.isScheduled) {
                    this.createNotificationSuccess({
                        message: this.$tc(
                            'klaviyo_integration_plugin.subscribers.schedule_synchronization.success'
                        )
                    });
                } else if (response.data.errorCode === 'SYNCHRONIZATION_IS_ALREADY_RUNNING') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.subscribers.schedule_synchronization.is_running'
                        )
                    });
                } else {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.subscribers.schedule_synchronization.failed'
                        )
                    });
                }
            }.bind(this)).catch(function () {
                this.createNotificationError({
                    message: this.$tc(
                        'klaviyo_integration_plugin.subscribers.schedule_synchronization.failed'
                    )
                });
            }.bind(this));
        },
        resetSubscribersSynchronizationState() {
            this.subscribersSynchronizationJobInteractor.resetSynchronizationState();
        },
        resetHistoricalEventsSynchronizationState() {
            this.historicalEventsJobInteractor.resetSynchronizationState();
        },
        getSynchronizationTooltipOptions(jobInteractor, lastJobStatusMessageKey, lastSuccessJobStatusMessageKey) {
            let notAvailable = this.$tc('klaviyo_integration_plugin.not_available');

            if (!jobInteractor || !jobInteractor.lastJob) {
                return {'disabled': true, 'message': notAvailable, 'width': 200};
            }

            const lastSyncStatus = jobInteractor.lastSynchronizationStatus
                ? jobInteractor.lastSynchronizationStatus
                : notAvailable;

            const lastSynchronizationInfoMessage = jobInteractor.lastSynchronizationDate
                ? this.$tc(
                    lastJobStatusMessageKey+'.finished',
                    0,
                    {
                        'status': lastSyncStatus,
                        'date': jobInteractor.lastSynchronizationDate
                    }
                )
                : this.$tc(
                    lastJobStatusMessageKey+'.not_finished',
                    0,
                    {
                        'status': lastSyncStatus
                    }
                );

            const lastSuccessSynchronizationDate = jobInteractor.lastSuccessSynchronizationDate
                ? jobInteractor.lastSuccessSynchronizationDate
                : notAvailable;

            const lastSuccessSynchronizationInfoMessage = this.$tc(
                lastSuccessJobStatusMessageKey,
                0,
                {'date': lastSuccessSynchronizationDate}
            );

            const message = lastSynchronizationInfoMessage + '<br/>' + lastSuccessSynchronizationInfoMessage;

            return {
                'disabled': false,
                'message': message,
                'width': 320
            };
        }
    }
});