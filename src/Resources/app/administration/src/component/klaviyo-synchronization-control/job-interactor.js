class JobInteractor {
    constructor(apiService, date) {
        this.apiService = apiService;
        this.lastSuccessJob = null;
        this.lastJob = null;
        this.lastSynchronizationStatus = '';
        this.lastSynchronizationDate = '';
        this.lastSuccessSynchronizationDate = '';
        this.isSynchronizationInProgress = false;
        this.isSynchronizationSuccess = false;
        this.date = date;
    }

    /**
     * @returns {Promise}
     */
    updateJobStatuses() {
        const promise = this.apiService.getJobStatus();
        promise.then(
            (result) => {
                if (result.data.lastSuccessJob) {
                    this.lastSuccessJob = result.data.lastSuccessJob;
                } else {
                    this.lastSuccessJob = null;
                }
                if (result.data.lastJob) {
                    this.lastJob = result.data.lastJob;
                } else {
                    this.lastJob = null;
                }

                this.updateLastSynchronizationStatus();
                this.updateLastSuccessSynchronizationDate();
            }
        ).catch(
            (error) => {
                console.error(
                    'Failed to get job status',
                    error
                );
            }
        );

        return promise;
    }

    updateLastSynchronizationStatus() {
        if (this.lastJob) {
            this.lastSynchronizationStatus = this.lastJob.status;

            if (!this.lastJob.finishedAt) {
                this.lastSynchronizationDate = null;
            } else {
                this.lastSynchronizationDate = this.date(
                    this.lastJob.finishedAt,
                    {
                        hour: '2-digit',
                        minute: '2-digit'
                    }
                );
            }
        } else {
            this.lastSynchronizationStatus = null;
            this.lastSynchronizationDate = null;
        }
    }

    updateLastSuccessSynchronizationDate() {
        if (this.lastSuccessJob && this.lastSuccessJob.finishedAt) {
            this.lastSuccessSynchronizationDate = this.date(
                this.lastSuccessJob.finishedAt,
                {
                    hour: '2-digit',
                    minute: '2-digit'
                }
            )
        } else {
            this.lastSuccessSynchronizationDate = null;
        }
    }

    /**
     * @returns {Promise}
     */
    scheduleSynchronization() {
        const promise = this.apiService.scheduleSynchronization();

        this.isSynchronizationInProgress = true;
        promise.then(function (response) {
            this.isSynchronizationSuccess = !!response.data.isScheduled;
        }.bind(this)).catch(function (error) {
            this.isSynchronizationSuccess = false;
        }.bind(this)).finally(function () {
            this.isSynchronizationInProgress = false
        }.bind(this));

        return promise;
    }

    resetSynchronizationState() {
        this.isSynchronizationInProgress = false;
        this.isSynchronizationSuccess = false;
    }
}
export default JobInteractor;