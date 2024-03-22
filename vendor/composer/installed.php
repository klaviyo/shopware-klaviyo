<?php return array(
    'root' => array(
        'pretty_version' => '2.8.1',
        'version' => '2.8.1.0',
        'type' => 'shopware-platform-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'od/sw6-klaviyo-integration',
        'dev' => true,
    ),
    'versions' => array(
        'od/sw6-job-scheduler' => array(
            'pretty_version' => '2.0.8',
            'version' => '2.0.8.0',
            'reference' => 'fc5441ec481ba81675ce814e9ca7d7ed00b7dcbf',
            'type' => 'library',
            'install_path' => __DIR__ . '/../od/sw6-job-scheduler',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'od/sw6-klaviyo-integration' => array(
            'pretty_version' => '2.8.1',
            'version' => '2.8.1.0',
            'type' => 'shopware-platform-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
    ),
);
