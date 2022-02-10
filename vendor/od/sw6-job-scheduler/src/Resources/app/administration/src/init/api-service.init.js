import OdRescheduleService
    from '../service/api/od-reschedule.service';
const { Application } = Shopware;
const initContainer = Application.getContainer('init');

Application.addServiceProvider(
    'OdRescheduleService',
    (container) => new OdRescheduleService(initContainer.httpClient, container.loginService),
);
