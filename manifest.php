<?php
$manifest = array(
    'name' => 'camunda',
    'acceptable_sugar_versions' => array(),
    'acceptable_sugar_flavors' => array('CE'),
    'author' => 'hardsoft321',
    'description' => 'Integration with Camunda',
    'is_uninstallable' => true,
    'published_date' => '2018-03-20',
    'type' => 'module',
    'dependencies' => array(
        array(
            'id_name' => 'SugarBeanMailer',
            'version' => '0'
        ),
    ),
    'version' => '0.2.0',
);
$installdefs = array(
    'id' => 'camunda',
    'copy' => array(
        array(
            'from' => '<basepath>/source/copy',
            'to' => '.'
        ),
    ),
);
