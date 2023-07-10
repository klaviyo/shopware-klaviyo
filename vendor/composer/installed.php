<?php return array(
    'root' => array(
        'pretty_version' => '2.0.1',
        'version' => '2.0.1.0',
        'type' => 'shopware-platform-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'od/sw6-klaviyo-integration',
        'dev' => true,
    ),
    'versions' => array(
        'od/sw6-job-scheduler' => array(
            'pretty_version' => '2.0.4',
            'version' => '2.0.4.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../od/sw6-job-scheduler',
            'aliases' => array(),
            'reference' => 'e30a8b12a1a7135bd7052262a1e693df2dd30119',
            'dev_requirement' => false,
        ),
        'od/sw6-klaviyo-integration' => array(
            'pretty_version' => '2.0.1',
            'version' => '2.0.1.0',
            'type' => 'shopware-platform-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
    ),
);
