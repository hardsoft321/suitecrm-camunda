<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'modules/CamundaProcesses/SugarCamunda.php';

class CamundaProcessesController extends SugarController
{
    public function loadBean()
    {
        parent::loadBean();
        $this->sugarBean = null;
        $this->bpmnList = null;
        if (!empty($_REQUEST['sugar_module']) && !empty($_REQUEST['sugar_record'])) {
            $this->sugarBean = BeanFactory::getBean($_REQUEST['sugar_module'], $_REQUEST['sugar_record']);
            if (!empty($this->sugarBean) && !empty($_REQUEST['field'])) {
                if (isset($this->sugarBean->field_defs[$_REQUEST['field']]['bpmn'])) {
                    $bpnm = $this->sugarBean->field_defs[$_REQUEST['field']]['bpmn'];
                    $this->bpmnList = is_array($bpnm) ? $bpnm : array($bpnm);
                }
            }
        }
    }

    public function action_Panel()
    {
        $this->view = '';
        if (empty($this->sugarBean)) {
            $GLOBALS['log']->error("CamundaProcessesController action_Panel: sugar bean not loaded");
            return;
        }
        $ss = new Sugar_Smarty();
        $ss->assign('bean', $this->sugarBean);
        $processInstances = SugarCamunda::loadProcessInstances($this->sugarBean, $this->bpmnList);
        $historyProcessInstances = SugarCamunda::loadHistoryProcessInstances($this->sugarBean, $this->bpmnList);
        header('Content-Type: text/html');
        if (empty($processInstances) && empty($historyProcessInstances)) {
            $processDefinitions = SugarCamunda::loadProcessDefinitions($this->sugarBean, $this->bpmnList);
            $ss->assign('processDefinitions', $processDefinitions);
            echo $ss->fetch('modules/CamundaProcesses/tpls/ProcessDefinitions.tpl');
        }
        else {
            $ss->assign('processInstances', array_merge($processInstances, $historyProcessInstances));
            echo $ss->fetch('modules/CamundaProcesses/tpls/ProcessInstances.tpl');
        }
    }

    public function action_Bpmn()
    {
        $this->view = '';
        if (empty($this->sugarBean) || !$this->sugarBean->ACLAccess('view')) {
            http_response_code(404);
            echo translate('ERROR_NO_RECORD');
            return;
        }
        echo SugarCamunda::getBpmnXmlByTaskId($_REQUEST['task_id']);
    }

    public function action_Start()
    {
        $this->view = '';
        if (empty($this->sugarBean)) {
            $GLOBALS['log']->error("CamundaProcessesController action_Start: sugar bean not loaded");
            return;
        }
        $camunda = SugarCamunda::getJsonClient();

        $form = $camunda->get("/process-definition/{$_POST['definition_id']}/startForm");
        $variables = [];
        //TODO: code duplication in work with form, see action_SaveTask, SugarCamunda loadProcessDefinitions and loadProcessInstances
        if (strpos($form['key'], "embedded:app:") === 0) {
            $url = SugarCamunda::getUrl()
                . str_replace("embedded:app:", $form['contextPath'] . '/', $form['key'])
                ."?userId={$GLOBALS['current_user']->user_name}&noCache=".rand();
            $html = file_get_contents($url);
            $xml = simplexml_load_string($html);
            if (!empty($xml)) {
                $inputs = $xml->xpath('.//input[@cam-variable-type and @cam-variable-name and @name]');
                foreach ($inputs as $input) {
                    $variables[(string) $input['cam-variable-name']] = [
                        'value' => $_POST[(string) $input['name']],
                        'type' => (string) $input['cam-variable-type'],
                    ];
                }
            }
        }
        else {
            $definitionVariables = $camunda->get("/process-definition/{$_POST['definition_id']}/form-variables");
            foreach ($definitionVariables as $name => $var) {
                $variables[$name] = [
                    'value' => $_POST[$name],
                    'type' => $var['type'],
                ];
            }
        }

        $process = $camunda->post("/process-definition/{$_POST['definition_id']}/submit-form", [
            'businessKey' => $_POST['sugar_record'],
            'variables' => $variables,
        ]);
        if (empty($process) || empty($process->id)) {
            $GLOBALS['log']->error("CamundaProcessesController: unable to start process instance for definition {$_POST['definition_id']}. Response: ".var_export($process, true));
        }

        $this->redirectToBean();
    }

    public function action_SaveTask()
    {
        $this->view = '';
        if (empty($this->sugarBean)) {
            $GLOBALS['log']->error("CamundaProcessesController action_SaveTask: sugar bean not loaded");
            return;
        }
        $camunda = SugarCamunda::getJsonClient();
        $camundaRaw = SugarCamunda::getClient();

        $taskId = $_POST['task_id'];
        $task = $camunda->get("/task/{$taskId}");
        $assigneeId = null;
        if (!empty($task['assignee'])) {
            $user = $GLOBALS['db']->fetchOne("SELECT id FROM users WHERE user_name = "
                .$GLOBALS['db']->quoted($task['assignee'])." AND deleted = 0");
            if (!empty($user)) {
                $assigneeId = $user['id'];
            }
        }
        $canSave = SugarCamunda::canSaveTask($assigneeId);
        if (!$canSave) {
            sugar_die('Access denied');
        }

        $form = $camunda->get("/task/{$taskId}/form");
        $variables = [];
        $html = '';
        if (strpos($form['key'], "embedded:app:") === 0) {
            $url = SugarCamunda::getUrl()
                . str_replace("embedded:app:", $form['contextPath'] . '/', $form['key'])
                ."?userId={$GLOBALS['current_user']->user_name}&noCache=".rand();
            $html = file_get_contents($url);
        }
        else {
            try {
                $html = $camundaRaw->get("/task/{$taskId}/rendered-form");
            }
            catch (Exception $ex) {
            }
        }
        if ($html) {
            $doc = new DOMDocument();
            $doc->strictErrorChecking = false;
            $doc->loadHTML($html);
            $xml = simplexml_import_dom($doc);
            if (!empty($xml)) {
                $inputs = $xml->xpath('.//*[@cam-variable-type and @cam-variable-name and @name]');
                foreach ($inputs as $input) {
                    $variables[(string) $input['cam-variable-name']] = [
                        'value' => $_POST[(string) $input['name']],
                        'type' => (string) $input['cam-variable-type'],
                    ];
                }
            }
        }

        if (empty($variables)) { //TODO: send no variables
            $variables['_dummy'] = [
                'value' => "",
                'type' => "string",
            ];
        }

        if (!empty($_POST['complete'])) {
            $camunda->post("/task/{$taskId}/submit-form", [
                'variables' => $variables,
            ]);
            SugarCamunda::notifyTaskCompleted($this->sugarBean, $task['name']);
        }
        else {
            //TODO: this request resets assignee
            $camunda->post("/task/{$taskId}/resolve", [
                'variables' => $variables,
            ]);
        }

        $this->redirectToBean();
    }

    public function action_Assign()
    {
        $this->view = '';
        if (empty($this->sugarBean)) {
            $GLOBALS['log']->error("CamundaProcessesController action_Assign: sugar bean not loaded");
            return;
        }
        $camunda = SugarCamunda::getJsonClient();

        $taskId = $_POST['task_id'];
        $newAssingedUserId = $_POST['assigned_user_id'];
        $task = $camunda->get("/task/{$taskId}");
        $roles = SugarCamunda::getTaskCandidateRoles($taskId);
        $users = empty($roles)
            ? UserList::getAllGroupUsers($this->sugarBean)
            : UserList::getGroupUsersWithRoles($this->sugarBean, $roles);

        $canAssign = SugarCamunda::canAssignTask($this->sugarBean, $users);
        if (!$canAssign) {
            sugar_die('Access denied');
        }

        if (!empty($newAssingedUserId) && !isset($users[$newAssingedUserId])) {
            sugar_die('Cannot assign this user');
        }

        $newAssignee = '';
        if (!empty($newAssingedUserId)) {
            $user = BeanFactory::getBean('Users', $newAssingedUserId);
            if (!$user) {
                sugar_die('Cannot assign this user (user not found)');
            }
            $newAssignee = $user->user_name;
        }

        $camunda->post("/task/{$taskId}/identity-links", [
            'userId' => $newAssignee,
            'type' => 'assignee',
        ]);
        if (!empty($newAssignee) && $user->id != $GLOBALS['current_user']->id
            && $newAssignee != $task['assignee'])
        {
            SugarCamunda::notifyTaskAssignedUser($this->sugarBean, $user);
        }

        $this->redirectToBean();
    }

    protected function redirectToBean()
    {
        $this->return_module = $this->sugarBean->module_name;
        $this->return_id = $this->sugarBean->id;
        parent::post_save();
    }
}
