<?php
$manifest = array(
    'name' => 'camunda-example-ru',
    'acceptable_sugar_versions' => array(),
    'acceptable_sugar_flavors' => array('CE'),
    'author' => 'hardsoft321',
    'description' => 'Example process of AOS_Quotes Camunda Integration (Russian language)',
    'is_uninstallable' => true,
    'published_date' => '2023-01-31',
    'type' => 'module',
    'version' => '0.0.1',
    'dependencies' => array(
      array(
       'id_name' => 'camunda',
       'version' => '0.2'
      ),
    ),

);
$installdefs = array(
    'id' => 'camunda-example-ru',
    'copy' => array(
        array(
            'from' => '<basepath>/source/copy',
            'to' => '.'
        ),
    ),
);
