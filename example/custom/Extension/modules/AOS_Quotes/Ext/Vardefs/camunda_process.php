<?php

$dictionary['AOS_Quotes']['fields']['camunda_process'] = array (
    'name' => 'camunda_process',
    'type' => 'CamundaProcess',
    'source'=>'non-db',
    'inline_edit' => false,
    'vname'=>'LBL_BUSINESS_PROCESS',
    'processes' => array(
        'quoteApproval' => array(
            'include' => 'modules/CamundaProcesses/CamundaProcess.php',
            'class' => 'CamundaProcess',
        ),
    ),
);

$dictionary['AOS_Quotes']['fields']['camunda_history'] = array(
    'name' => 'camunda_history',
    'type' => 'CamundaProcess',
    'source' => 'non-db',
    'inline_edit' => false,
    'vname' => 'LBL_BUSINESS_PROCESS',
    'show_process_instances' => false,
    'show_historic_processes' => true,
    'processes' => $dictionary['AOS_Quotes']['fields']['camunda_process']['processes'],
);
