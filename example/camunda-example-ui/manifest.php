<?php
$manifest = array(
    'name' => 'camunda-example-ui',
    'acceptable_sugar_versions' => array(),
    'acceptable_sugar_flavors' => array('CE'),
    'author' => 'hardsoft321',
    'description' => 'Example process of AOS_Quotes Camunda Integration (UI overriding)',
    'is_uninstallable' => true,
    'published_date' => '2023-01-24',
    'type' => 'module',
    'version' => '0.0.1',
    'dependencies' => array(
      array(
       'id_name' => 'camunda-example',
       'version' => '0.0'
      ),
    ),

);
$installdefs = array(
    'id' => 'camunda-example-ui',
    'copy' => array(
        array(
            'from' => '<basepath>/source/copy',
            'to' => '.'
        ),
    ),
);
