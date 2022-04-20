const ApiService = Shopware.Classes.ApiService;

class OdRescheduleService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'od-job') {
        super(httpClient, loginService, apiEndpoint);
    }

    rescheduleJob(jobId) {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .post(
                `_action/${this.getApiBasePath()}/reschedule`,
                {
                    params: { jobId },
                    headers: headers,
                },
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default OdRescheduleService;
