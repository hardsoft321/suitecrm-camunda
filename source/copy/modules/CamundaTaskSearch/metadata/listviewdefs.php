<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

$listViewDefs['CamundaTaskSearch'] = array(
    'PROCESSDEFINITIONNAME' => array(
        'label' => 'LBL_PROCESSDEFINITIONNAME',
        'width' => 10,
        'link' => true,
        'sortable' => false,
        'default' => true,
    ),
    // 'PROCESSDEFINITIONKEY' => array(
    //     'label' => 'LBL_PROCESSDEFINITIONKEY',
    //     'width' => 10,
    //     'sortable' => false,
    //     'default' => false,
    // ),
    'TASKDEFINITIONNAME' => array(
        'label' => 'LBL_TASKDEFINITIONNAME',
        'width' => 10,
        'default' => true,
    ),
    // 'TASKDEFINITIONKEY' => array(
    //     'label' => 'LBL_TASKDEFINITIONKEY',
    //     'width' => 10,
    //     'sortable' => false,
    //     'default' => false,
    // ),
    'BUSINESSNAME' => array(
        'label' => 'LBL_BUSINESSNAME',
        'width' => 10,
        'default' => true,
        'sortable' => false,
    ),
    'ASSIGNEENAME' => array(
        'label' => 'LBL_ASSIGNEENAME',
        'width' => 10,
        'link' => true,
        'module' => 'Employees',
        'id' => 'ASSIGNEEID',
        'sortable' => false,
        'default' => true,
    ),
    'ASSIGNEE' => array(
        'label' => 'LBL_ASSIGNEE',
        'width' => 10,
        'default' => false,
    ),
    'DATE_ENTERED' => array(
        'label' => 'LBL_DATE_ENTERED',
        'width' => 10,
        'default' => true,
    ),
);
