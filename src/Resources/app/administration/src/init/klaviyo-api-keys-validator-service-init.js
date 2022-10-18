import KlaviyoApiKeyValidatorService from "../services/api/klaviyo-api-keys-validator-service";

Shopware.Service().register('klaviyoApiKeyValidatorService', (container) => {
    const initContainer = Shopware.Application.getContainer('init');
    return new KlaviyoApiKeyValidatorService(initContainer.httpClient, container.loginService);
});
