const {ApiService} = Shopware.Classes;

class KlaviyoSubscribersSynchronizationApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'klaviyo') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'klaviyoSubscribersSynchronizationApiService';
    }

    scheduleSynchronization(fromDate, tillDate) {
        const headers = this.getBasicHeaders();
        return this.httpClient
            .post('_action/klaviyo/subscribers/synchronization/schedule', {
                fromDate: fromDate, tillDate: tillDate
            }, {headers});
    }
}

export default KlaviyoSubscribersSynchronizationApiService;
