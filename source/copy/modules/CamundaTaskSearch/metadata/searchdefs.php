<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

$searchdefs['CamundaTaskSearch'] = array(
    'templateMeta' => array(
        'maxColumns' => 3,
        'maxColumnsBasic' => 3,
        'widths' => array(
            'label' => '10',
            'field' => '30',
        ),
    ),
    'layout' => array(
        'basic_search' => array(
            array(
                'name' => 'current_user_only',
                'label' => 'LBL_CURRENT_USER_FILTER',
                'type' => 'bool',
            ),
        ),
        'advanced_search' => array(
            'processDefinitionKey',
            'taskDefinitionKey',
            'assignee' => array(
                'name' => 'assignee',
                'type' => 'enum',
                'label' => 'LBL_ASSIGNED_TO',
                'function' => array(
                    'name' => 'get_user_array',
                    'params' => array(0 => true),
                ),
                'default' => true,
                'width' => '10%',
                'displayParams' => array(
                    'size' => 1,
                ),
            ),
        ),
    ),
);
