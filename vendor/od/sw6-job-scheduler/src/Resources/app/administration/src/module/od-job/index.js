import './page/od-job-listing-index';

import enGB from './snippet/en-GB.json';
import deDE from './snippet/de-DE.json';

const {Module} = Shopware;

Module.register('od-job-listing', {
    type: 'plugin',
    title: 'job-listing.general.title',
    description: 'job-listing.general.description',
    color: '#F88962',
    icon: 'default-avatar-multiple',

    snippets: {
        'en-GB': enGB,
        'de-DE': deDE
    },

    routes: {
        detail: {
            path: 'detail/:id/back/:backPath',
            props: {
                default: ($route) => {
                    return { jobId: $route.params.id };
                },
            },
        }
    },
})
