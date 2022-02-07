import './page/od-job-listing-index';
import './page/od-job-detail-index';

import enGB from './snippet/en-GB.json';

const {Module} = Shopware;

Module.register('od-job-listing', {
    type: 'plugin',
    title: 'job-listing.general.title',
    description: 'job-listing.general.description',
    color: '#F88962',
    icon: 'default-avatar-multiple',

    snippets: {
        'en-GB': enGB,
    },

    routes: {
        detail: {
            component: 'od-job-detail-index',
            path: 'detail/:id/back/:backPath',
            props: {
                default: ($route) => {
                    return { jobId: $route.params.id };
                },
            },
        }
    },
})
