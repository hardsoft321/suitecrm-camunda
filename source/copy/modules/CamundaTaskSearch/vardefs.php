<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

$dictionary['CamundaTaskSearch'] = array(
    'table' => '',
    'audited' => false,
    'fields' => array(
        'processDefinitionKey' => array(
            'name' => 'processDefinitionKey',
            'vname' => 'LBL_PROCESSDEFINITIONKEY',
            'type' => 'enum',
            'function' => array(
                'name' => 'CamundaProcessDefinition::listKeyNames',
                'include' => 'modules/CamundaProcesses/CamundaProcessDefinition.php',
            ),
        ),
        'processDefinitionName' => array(
            'name' => 'processDefinitionName',
            'vname' => 'LBL_PROCESSDEFINITIONNAME',
            'type' => 'varchar',
        ),
        'taskDefinitionKey' => array(
            'name' => 'taskDefinitionKey',
            'vname' => 'LBL_TASKDEFINITIONKEY',
            'type' => 'enum',
            'function' => array(
                'name' => 'CamundaProcessDefinition::listTaskDefNames',
                'include' => 'modules/CamundaProcesses/CamundaProcessDefinition.php',
            ),
        ),
        'taskDefinitionName' => array(
            'name' => 'taskDefinitionName',
            'vname' => 'LBL_TASKDEFINITIONNAME',
            'type' => 'varchar',
        ),
        'assignee' => array(
            'name' => 'assignee',
            'vname' => 'LBL_ASSIGNEE',
            'type' => 'varchar',
        ),
        'assigneeName' => array(
            'name' => 'assigneeName',
            'vname' => 'LBL_ASSIGNEENAME',
            'type' => 'varchar',
        ),
        'created' => array(
            'name' => 'created',
            'vname' => 'LBL_CREATED',
            'type' => 'varchar',
        ),
        'businessName' => array(
            'name' => 'created',
            'vname' => 'LBL_BUSINESSNAME',
            'type' => 'varchar',
        ),
    ),
);
