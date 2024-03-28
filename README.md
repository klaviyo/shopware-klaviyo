# Klaviyo Integration 

This plug-in integrates a new module in Shopware which allows the integration of [Klaviyo](https://www.klaviyo.com/)

The main feature of this plugin is the integration of [Klaviyo Event Tracking](https://help.klaviyo.com/hc/en-us/articles/115005082927-Integrate-a-Custom-Ecommerce-Cart-or-Platform)

The plugin offers the following features:

* Tracking customer data
* Tracking subscribers
* Tracking website activity
* Tracking order activity
* Tracking products

Requirements
---
* Shopware >= 6.4.0.0

Embedded Dependencies:
---
* OD Scheduler ^1.0.0

Documentation
---
* [Installation instruction](./src/Resources/doc/installation.md)
* [Capabilities and restrictions](./src/Resources/doc/capabilities_and_restrictions.md)
* [Plugin Implementation Overview](./src/Resources/doc/plugin_implementation_overview.md)

Testing
---
To perform tests, phpunit.xml.dist must be configured properly (at least KLAVIYO_PRIVATE_KEY const parameter), overwise this tests willl be skipped.
Run the following command to execute common testsuite in module root:
```
./bin/phpunit.sh --testsuite General
```
How to run load tests:
```
./bin/phpunit.sh --testsuite Load
```

Test: 2
