<?php return array(
    'root' => array(
        'name' => 'od/sw6-klaviyo-integration',
        'pretty_version' => '1.6.0',
        'version' => '1.6.0.0',
        'reference' => NULL,
        'type' => 'shopware-platform-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'od/sw6-job-scheduler' => array(
            'pretty_version' => '1.0.5',
            'version' => '1.0.5.0',
            'reference' => 'c97d38a0d15fab551800c17a189cd85474efdc0a',
            'type' => 'library',
            'install_path' => __DIR__ . '/../od/sw6-job-scheduler',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'od/sw6-klaviyo-integration' => array(
            'pretty_version' => '1.6.0',
            'version' => '1.6.0.0',
            'reference' => NULL,
            'type' => 'shopware-platform-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
