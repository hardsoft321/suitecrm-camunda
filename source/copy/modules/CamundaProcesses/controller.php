<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'modules/CamundaProcesses/SugarCamunda.php';
require_once 'modules/CamundaProcesses/CamundaProcessDefinition.php';

class CamundaProcessesController extends SugarController
{
    protected $sugarBean;
    protected $camundaFieldName;

    public function loadBean()
    {
        parent::loadBean();
        $this->sugarBean = null;
        if (!empty($_REQUEST['sugar_module']) && !empty($_REQUEST['sugar_record'])) {
            $this->sugarBean = BeanFactory::getBean($_REQUEST['sugar_module'], $_REQUEST['sugar_record']);
            $this->camundaFieldName = $_REQUEST['camunda_field_name'];
        }
    }

    public function action_Panel()
    {
        $this->view = '';
        if (empty($this->sugarBean)) {
            echo translate('ERROR_NO_RECORD');
            return;
        }
        $ss = new Sugar_Smarty();
        $ss->assign('bean', $this->sugarBean);
        $ss->assign('camunda_field_name', $this->camundaFieldName);
        $ss->assign('current_user_is_admin', $GLOBALS['current_user']->isAdmin());
        $show_process_instances = !isset($this->sugarBean->field_defs[$this->camundaFieldName]['show_process_instances'])
                || $this->sugarBean->field_defs[$this->camundaFieldName]['show_process_instances'];
        $show_historic_processes = !isset($this->sugarBean->field_defs[$this->camundaFieldName]['show_historic_processes'])
                || $this->sugarBean->field_defs[$this->camundaFieldName]['show_historic_processes'];
        $processInstances = $show_process_instances ?
            SugarCamunda::loadProcessInstances($this->sugarBean, $this->camundaFieldName) : array();
        $historyProcessInstances = $show_historic_processes ?
                SugarCamunda::loadHistoryProcessInstances($this->sugarBean, $this->camundaFieldName) : array();
        header('Content-Type: text/html');
        if (!empty($GLOBALS['camunda_urgent_task_requires_page_reload'])) {
            if (!empty($GLOBALS['camunda_urgent_task_requires_page_reload']['url'])) {
                echo translate('LBL_LOADING')
                     . '<script>location = ' . json_encode($GLOBALS['camunda_urgent_task_requires_page_reload']['url']) . ';</script>';
                return;
            }
            echo translate('LBL_LOADING') . '<script>location.reload();</script>'; //TODO: it can be faster with SUGAR.ajaxUI
            return;
        }
        // var_dump($processInstances[0].tasks);
        // var_dump($processInstances[0]);
        if (empty($processInstances) && $show_process_instances) {
            $processDefinitions = SugarCamunda::loadProcessDefinitions($this->sugarBean, $this->camundaFieldName);

            $ss->assign('processDefinitions', $processDefinitions);
            echo $ss->fetch('modules/CamundaProcesses/tpls/ProcessDefinitions.tpl');
        }
        if (!empty($processInstances) || !empty($historyProcessInstances)) {
            $ss->assign('processInstances', array_merge($processInstances, $historyProcessInstances));
            echo $ss->fetch('modules/CamundaProcesses/tpls/ProcessInstances.tpl');
        }
        if (empty($processDefinitions) && empty($processInstances) && empty($historyProcessInstances)) {
            echo translate('LBL_NO_DATA');
        }
    }

    public function action_Bpmn()
    {
        $this->view = '';
        if (empty($this->sugarBean) || !$this->sugarBean->ACLAccess('view')) {
            echo translate('ERROR_NO_RECORD');
            return;
        }
        header('Content-Type: application/xml; charset=utf-8');
        $processDefinitionId = SugarCamunda::clean_string($_REQUEST['definition_id']);
        echo CamundaProcessDefinition::getBpmnXml($processDefinitionId);
    }

    public function action_StartProcess()
    {
        $this->view = '';
        if (empty($this->sugarBean)) {
            echo translate('ERROR_NO_RECORD');
            return;
        }
        if (empty($_POST['definition_id'])) {
            echo "definition_id is empty";
            return;
        }
        $processDefinitionId = SugarCamunda::clean_string($_POST['definition_id']);
        $process = SugarCamunda::getCamundaProcess(array('id' => $processDefinitionId), $this->sugarBean, $this->camundaFieldName);
        if (!$process) {
            echo "Unable to start process";
            return;
        }
        if (!$process->startAccess()) {
            echo "No access";
            return;
        }
        $variables = $process->getStartFormVariables();
        $process->fillStartVariables($_POST, $variables);
        $result = $process->start($variables);
        if (empty($result) || empty($result['id'])) {
            $GLOBALS['log']->error("CamundaProcessesController: unable to start process instance for definition {$processDefinitionId}."
                . " Response: ".var_export($result, true));
        }
        $this->redirectToBean();
    }

    public function action_DeleteProcess()
    {
        $this->view = '';
        if (!$GLOBALS['current_user']->isAdmin()) {
            echo 'Only admin access';
            return;
        }
        if (empty($_POST['process_id'])) {
            echo 'process_id is empty';
            return;
        }
        $processId = SugarCamunda::clean_string($_POST['process_id']);
        SugarCamunda::getJsonClient()->delete("/process-instance/{$processId}");
        $this->redirectToBean();
    }

    public function action_SaveTask()
    {
        $this->view = '';
        if (empty($this->sugarBean)) {
            echo translate('ERROR_NO_RECORD');
            return;
        }
        if (empty($_POST['task_id'])) {
            echo "CamundaProcessesController action_SaveTask: task_id is empty";
            return;
        }
        $taskId = SugarCamunda::clean_string($_POST['task_id']);
        $task = SugarCamunda::getJsonClient()->get("/task/{$taskId}");
        $process = SugarCamunda::getCamundaProcess(array('id' => $task['processDefinitionId']), $this->sugarBean, $this->camundaFieldName);
        if (!$process) {
            echo 'No process';
            return;
        }
        $camundaTask = $process->getCamundaTask($task);
        $canSave = $camundaTask->saveAccess();
        if (!$canSave) {
            echo 'Access denied';
            return;
        }
        $variables = $camundaTask->getFormVariables();
        $camundaTask->fillTaskVariables($_POST, $variables);
        $camundaTask->setOwner($GLOBALS['current_user']); //use setOwner instead of assign, so assignment hook is not triggered
        $camundaTask->submitForm($variables);
        $this->redirectToBean();
    }

    public function action_Assign()
    {
        $this->view = '';
        if (empty($this->sugarBean)) {
            echo translate('ERROR_NO_RECORD');
            return;
        }
        $taskId = SugarCamunda::clean_string($_POST['task_id']);
        $newAssignedUserId = $_POST['assigned_user_id'];
        $task = SugarCamunda::getJsonClient()->get("/task/{$taskId}");
        $process = SugarCamunda::getCamundaProcess(array('id' => $task['processDefinitionId']), $this->sugarBean, $this->camundaFieldName);
        if (!$process) {
            echo 'No process';
            return;
        }
        $camundaTask = $process->getCamundaTask($task);
        $canAssign = $camundaTask->assignAccess();
        if (!$canAssign) {
            echo 'Access denied';
            return;
        }
        $newAssignedUser = null;
        if (!empty($newAssignedUserId)) {
            $newAssignedUser = BeanFactory::getBean('Users', $newAssignedUserId);
            if (!$newAssignedUser) {
                echo 'Cannot assign this user (user not found)';
                return;
            }
            if (!$camundaTask->canBeAssigned($newAssignedUser)) {
                echo 'Cannot assign this user';
                return;
            }
        }
        $camundaTask->assign($newAssignedUser);
        $this->redirectToBean();
    }

    protected function redirectToBean()
    {
        $url = "index.php?module={$this->sugarBean->module_name}&action=DetailView&return_field={$this->camundaFieldName}&record={$this->sugarBean->id}";
        $this->set_redirect($url);
    }
}
