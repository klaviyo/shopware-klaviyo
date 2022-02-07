class JobInteractor {
    constructor(apiService, date) {
        this.apiService = apiService;
        this.isSynchronizationInProgress = false;
        this.isSynchronizationSuccess = false;
        this.date = date;
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
