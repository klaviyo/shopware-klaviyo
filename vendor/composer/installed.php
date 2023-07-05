<?php return array(
    'root' => array(
        'pretty_version' => '2.0.0',
        'version' => '2.0.0.0',
        'type' => 'shopware-platform-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'od/sw6-klaviyo-integration',
        'dev' => true,
    ),
    'versions' => array(
        'od/sw6-job-scheduler' => array(
            'pretty_version' => '2.0.3',
            'version' => '2.0.3.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../od/sw6-job-scheduler',
            'aliases' => array(),
            'reference' => '1888e6f6afbe963313ed26b5062563f05576642a',
            'dev_requirement' => false,
        ),
        'od/sw6-klaviyo-integration' => array(
            'pretty_version' => '2.0.0',
            'version' => '2.0.0.0',
            'type' => 'shopware-platform-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
    ),
);
