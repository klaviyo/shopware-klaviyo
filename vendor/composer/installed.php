<?php return array(
    'root' => array(
        'pretty_version' => '1.0.2',
        'version' => '1.0.2.0',
        'type' => 'shopware-platform-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'reference' => NULL,
        'name' => 'od/sw6-klaviyo-integration',
        'dev' => false,
    ),
    'versions' => array(
        'od/sw6-job-scheduler' => array(
            'pretty_version' => '1.0.0',
            'version' => '1.0.0.0',
            'type' => 'library',
            'install_path' => __DIR__ . '/../od/sw6-job-scheduler',
            'aliases' => array(),
            'reference' => '635a3b27a77fc7b54980360d597d43b95a7580f6',
            'dev_requirement' => false,
        ),
        'od/sw6-klaviyo-integration' => array(
            'pretty_version' => '1.0.2',
            'version' => '1.0.2.0',
            'type' => 'shopware-platform-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'reference' => NULL,
            'dev_requirement' => false,
        ),
    ),
);
