Klaviyo Integration Plugin 
============================

### Overview

This Shopware 6 plugin provides an integration between [Klaviyo Event Tracking](https://help.klaviyo.com/hc/en-us/articles/115005082927-Integrate-a-Custom-Ecommerce-Cart-or-Platform)
and Shopware 6

* [Installation instruction](./Resources/doc/installation.md)
* [Capabilities and restrictions](./Resources/doc/capabilities_and_restrictions.md)
* [Plugin Implementation Overview](Resources/doc/plugin_implementation_overview.md)

### Testing

To perform tests, phpunit.xml.dist must be configured properly (at least KLAVIYO_PRIVATE_KEY const parameter), overwise this tests willl be skipped.
Run the following command to execute common testsuite in module root:
```
./bin/phpunit.sh --testsuite General
```
How to run load tests:
```
./bin/phpunit.sh --testsuite Load
```
