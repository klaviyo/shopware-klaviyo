import header from './header.xml.twig';
import body from './body.xml.twig';
import footer from './footer.xml.twig';

Shopware.Service('exportTemplateService').registerProductExportTemplate({
    name: 'klaviyo-product-feed',
    translationKey: 'klaviyo_integration_plugin.productComparison.templates.template-label.klaviyo-product-feed',
    headerTemplate: header.trim(),
    bodyTemplate: body,
    footerTemplate: footer.trim(),
    fileName: 'klaviyo.xml',
    encoding: 'UTF-8',
    fileFormat: 'xml',
    generateByCronjob: false,
    interval: 86400,
});
