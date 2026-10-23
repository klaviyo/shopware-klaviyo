import template from './klaviyo-integration-settings-synchronization-control.html.twig';
import JobInteractor from './job-interactor';

const { Component, Mixin } = Shopware;
const { date } = Shopware.Utils.format;

Component.register('klaviyo-integration-settings-synchronization-control', {
    template,

    inject: [
        'klaviyoHistoricalEventsSynchronizationApiService',
        'klaviyoSubscribersSynchronizationApiService',
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        actualConfigData: {
            type: Object,
            required: true,
        },
        allConfigs: {
            type: Object,
            required: true,
        },
        selectedSalesChannelId: {
            type: String,
            required: false,
            default: null,
        },
    },

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
            lastCalledFunction: null,
            historicalEventsFromDate: '',
            historicalEventsTillDate: '',
            subscribersFromDate: '',
            subscribersTillDate: ''
        }
    },

    computed: {
        isSwitchingFunctions() {
            return (funcName) => this.lastCalledFunction && this.lastCalledFunction !== funcName;
        }
    },

    methods: {
        checkBoolFieldInheritance(value) {
            return typeof value !== 'boolean';
        },

        proceedHistoricalEventsSynchronization() {

            // mt-datepicker emits null (not '') when a date is cleared, so guard on truthiness
            const fromDate = this.historicalEventsFromDate ? new Date(this.historicalEventsFromDate) : null;
            const tillDate = this.historicalEventsTillDate ? new Date(this.historicalEventsTillDate) : null;

            if (fromDate && tillDate && (fromDate > tillDate)) {
                this.createNotificationError({
                    message: this.$tc('klaviyo_integration_plugin.historical_events_tracking.modal.invalid_date_range')
                });
                return;
            }

            this.scheduleHistoricalEventsSynchronization(
                this.historicalEventsFromDate || '',
                this.historicalEventsTillDate || ''
            );
        },

        scheduleHistoricalEventsSynchronization(fromDate, tillDate) {
            if (this.isSwitchingFunctions('scheduleHistoricalEventsSynchronization')) {
                this.showConfirmation(
                    this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarningPopup.confirmActionTitle'),
                    this.$t('klaviyo-integration-settings.configs.doubleProcessLaunchWarning.label', {
                        entityName: this.$tc('klaviyo_integration_plugin.subscribers.schedule_synchronization.button_label'),
                        docUrl: '(<a href="https://developers.klaviyo.com/en/docs/rate_limits_and_error_handling#rate-limits" target="_blank">see Rate limits, status codes, and errors</a>)'
                    }),
                    () => {
                        this.lastCalledFunction = null;
                        this.performHistoricalEventsSynchronization(fromDate, tillDate);
                    }
                );
            } else {
                this.performHistoricalEventsSynchronization(fromDate, tillDate);
            }
        },

        performHistoricalEventsSynchronization(fromDate, tillDate) {
            const promise = this.historicalEventsJobInteractor.scheduleSynchronization(fromDate, tillDate);
            promise.then(function (response) {
                if (response.data.isScheduled) {
                    this.lastCalledFunction = 'scheduleHistoricalEventsSynchronization';
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
                } else if (response.data.errorCode === 'SYNCHRONIZATION_IS_ALREADY_SCHEDULED') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.is_scheduled'
                        )
                    });
                } else if (response.data.errorCode === 'INVALID_DATE_FORMAT') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.invalid_date_format'
                        )
                    });
                } else if (response.data.errorCode === 'INVALID_DATE_RANGE') {
                    this.createNotificationWarning({
                        message: this.$tc('klaviyo_integration_plugin.historical_events_tracking.modal.invalid_date_range')
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

        proceedSubscribersSynchronization() {

            // mt-datepicker emits null (not '') when a date is cleared, so guard on truthiness
            const fromDate = this.subscribersFromDate ? new Date(this.subscribersFromDate) : null;
            const tillDate = this.subscribersTillDate ? new Date(this.subscribersTillDate) : null;

            if (fromDate && tillDate && (fromDate > tillDate)) {
                this.createNotificationError({
                    message: this.$tc('klaviyo_integration_plugin.subscribers.modal.invalid_date_range')
                });
                return;
            }

            this.scheduleSubscribersSynchronization(
                this.subscribersFromDate || '',
                this.subscribersTillDate || ''
            );
        },

        scheduleSubscribersSynchronization(fromDate, tillDate) {
            if (this.isSwitchingFunctions('scheduleSubscribersSynchronization')) {
                this.showConfirmation(
                    this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarningPopup.confirmActionTitle'),
                    this.$t('klaviyo-integration-settings.configs.doubleProcessLaunchWarning.label', {
                        entityName: this.$tc('klaviyo_integration_plugin.historical_events_tracking.schedule_synchronization.button_label'),
                        docUrl: '(<a href="https://developers.klaviyo.com/en/docs/rate_limits_and_error_handling#rate-limits" target="_blank">see Rate limits, status codes, and errors</a>)'
                    }),
                    () => {
                        this.lastCalledFunction = null;
                        this.performSubscribersSynchronization(fromDate, tillDate);
                    }
                );
            } else {
                this.performSubscribersSynchronization(fromDate, tillDate);
            }
        },

        performSubscribersSynchronization(fromDate, tillDate) {
            const promise = this.subscribersSynchronizationJobInteractor.scheduleSynchronization(fromDate, tillDate);
            promise.then(function (response) {
                if (response.data.isScheduled) {
                    this.lastCalledFunction = 'scheduleSubscribersSynchronization';
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
                } else if (response.data.errorCode === 'SYNCHRONIZATION_IS_ALREADY_SCHEDULED') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.subscribers.schedule_synchronization.is_scheduled'
                        )
                    });
                } else if (response.data.errorCode === 'INVALID_DATE_FORMAT') {
                    this.createNotificationWarning({
                        message: this.$tc(
                            'klaviyo_integration_plugin.subscribers.schedule_synchronization.invalid_date_format'
                        )
                    });
                } else if (response.data.errorCode === 'INVALID_DATE_RANGE') {
                    this.createNotificationWarning({
                        message: this.$tc('klaviyo_integration_plugin.subscribers.modal.invalid_date_range')
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

        showConfirmation(title, message, onConfirm) {
            this.createNotification({
                title: title,
                message: message,
                variant: 'warning',
                autoClose: false,
                system: true,
                actions: [
                    {
                        label: this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarningPopup.confirmLabel'),
                        method: onConfirm
                    },
                    {
                        label: this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarningPopup.cancelLabel'),
                        method: () => {
                            this.createNotificationInfo({
                                message: this.$tc('klaviyo-integration-settings.configs.doubleProcessLaunchWarning.actionCanceled')
                            });
                        }
                    }
                ]
            });
        }
    }
});