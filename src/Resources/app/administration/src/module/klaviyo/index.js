import './pages/klaviyo-job-listing';
import './pages/klaviyo-integration-settings';
import './components/klaviyo-integration-settings-general';
import './components/klaviyo-integration-settings-customer';
import './components/klaviyo-integration-settings-synchronization-control';
import './components/klaviyo-integration-settings-icon';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const {Module} = Shopware;

Module.register('klaviyo-plugin', {
    type: 'plugin',
    title: 'klaviyo-integration-settings.title',
    description: 'klaviyo-job-listing.general.description',
    color: '#F88962',
    icon: 'default-avatar-multiple',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'klaviyo-job-listing',
            path: 'index'
        },
        settings: {
            component: 'klaviyo-integration-settings',
            path: 'settings',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            }
        }
    },

    settingsItem: {
        group: 'plugins',
        to: 'klaviyo.plugin.settings',
        label: "klaviyo-integration-settings.label",
        iconComponent: 'klaviyo-integration-settings-icon',
        backgroundEnabled: true
    },

    navigation: [
        {
            id: 'klaviyo',
            label: 'klaviyo-job-listing.menu.title',
            color: '#F88962',
            icon: 'default-avatar-multiple',
            parent: 'sw-marketing',
            path: 'klaviyo.plugin.index',
            position: 100
        }
    ],
})
