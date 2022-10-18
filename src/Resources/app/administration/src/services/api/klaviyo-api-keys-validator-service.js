const {ApiService} = Shopware.Classes;

class KlaviyoApiKeyValidatorService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'klaviyo') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'klaviyoApiKeyValidatorService';
    }

    validate(privateKey, publicKey, listName) {
        const headers = this.getBasicHeaders();
        return this.httpClient
            .post('/_action/od-api-key-validate', {
                "privateKey": privateKey, "publicKey": publicKey, "listName": listName
            }, {headers});
    }
}

export default KlaviyoApiKeyValidatorService;
