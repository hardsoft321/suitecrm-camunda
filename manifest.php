<?php
$manifest = array(
    'name' => 'camunda',
    'acceptable_sugar_versions' => array(),
    'acceptable_sugar_flavors' => array('CE'),
    'author' => 'hardsoft321',
    'description' => 'Integration with Camunda',
    'is_uninstallable' => true,
    'published_date' => '2023-01-27',
    'type' => 'module',
    'version' => '0.2.2',
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
