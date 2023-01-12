<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'modules/CamundaProcesses/CamundaForm.php';
require_once 'modules/CamundaProcesses/CamundaTask.php';

class CamundaProcess
{
    public $processDefinition;
    public $sugarBean;
    public $beanFieldName;
    public $userIdField = 'id';

    public function __construct($processDefinition, $bean, $fieldName)
    {
        $this->processDefinition = $processDefinition;
        $this->sugarBean = $bean;
        $this->beanFieldName = $fieldName;
    }

    public function getCamundaTask($taskProps)
    {
        return new CamundaTask($taskProps, $this->sugarBean);
    }

    public function loadProcessDefinition(&$processDefinition)
    {
        $processDefinition['form'] = SugarCamunda::getJsonClient()->get("process-definition/{$processDefinition['id']}/startForm");

        if (!empty($processDefinition['form']['key'])
            && strpos($processDefinition['form']['key'], "embedded:app:") === 0) {
            $url = SugarCamunda::getUrl()
                . str_replace("embedded:app:", $processDefinition['form']['contextPath'] . '/', $processDefinition['form']['key'])
                . "?userId={$GLOBALS['current_user']->{$this->userIdField}}&noCache=".rand();
            $processDefinition['form']['html'] = file_get_contents($url);
        }
        if (empty($processDefinition['form']['html'])) {
            try {
                $processDefinition['form']['html'] = SugarCamunda::getClient()->get(
                    "/process-definition/{$processDefinition['id']}/rendered-form");
            }
            catch (Exception $ex) {
                $GLOBALS['log']->error("CamundaProcess: no rendered-form for process definition "
                    . ' ' . $processDefinition['id'] . print_r($processDefinition['form'], true)
                    . ' ' . $ex->getMessage());
            }
        }
        $processDefinition['startAccess'] = $this->startAccess();
    }

    public function loadProcessInstance(&$processInstance)
    {
        if (empty($processInstance['ended']) && empty($processInstance['suspended'])) {
            // $current_user = $GLOBALS['current_user'];
            // if (!empty($GLOBALS['sugar_config']['camunda']['sugar_users'])) {
            //     $user_name = reset($GLOBALS['sugar_config']['camunda']['sugar_users']);
            //     $user = BeanFactory::newBean('Users')->retrieve_by_string_fields(array(
            //         'user_name' => $user_name,
            //     ));
            //     if ($user) {
            //         $GLOBALS['current_user'] = $user;
            //     }
            // }
            $done = $this->runUrgentExternalTasks();
            // $GLOBALS['current_user'] = $current_user;
            if (!empty($done['refreshProcess'])) {
                try {
                    $processDefinition = $processInstance['processDefinition'];
                    $processInstance = SugarCamunda::getJsonClient()->get("/process-instance/{$processInstance['id']}");
                    $processInstance['processDefinition'] = $processDefinition;
                } catch (Exception $e) {
                    $processInstance = null;
                    return;
                }
            }
        }
        $processInstance['variables'] = SugarCamunda::getJsonClient()->get("/process-instance/{$processInstance['id']}/variables");
        $this->translateVariables($processInstance['variables'], $processInstance['processDefinition']['id']);
        $processInstance['tasks'] = SugarCamunda::getJsonClient()->get('task', array(
            'processInstanceId' => $processInstance['id'],
            'sortBy' => 'created',
            'sortOrder' => 'desc',
        ));
        foreach ($processInstance['tasks'] as &$task) {
            $this->loadTask($task);
        }
        unset($task);
        $processInstance['history'] = $this->getHistoryActivityInstances($processInstance['id']);
    }

    public function getStartFormVariables()
    {
        $form = SugarCamunda::getJsonClient()->get("/process-definition/{$this->processDefinition['id']}/startForm");
        if (strpos($form['key'], "embedded:app:") === 0) {
            $url = SugarCamunda::getUrl()
                . str_replace("embedded:app:", $form['contextPath'] . '/', $form['key'])
                ."?userId={$GLOBALS['current_user']->{$this->userIdField}}&noCache=".rand();
            $html = file_get_contents($url);
            return CamundaForm::parseFormVariables($html);
        }
        elseif (strpos($form['key'], "embedded:engine:") === 0) {
            return SugarCamunda::getJsonClient()->get("/process-definition/{$this->processDefinition['id']}/form-variables");
        }
        return false;
    }

    public function fillStartVariables($unsafeSource, &$variables)
    {
        CamundaForm::fillVariables($unsafeSource, $variables);
    }

    public function startAccess()
    {
        return true;
    }

    public function start($variables)
    {
        foreach ($variables as &$var) {
            if (isset($var['valueInfo']) && empty($var['valueInfo'])) {
                unset($var['valueInfo']);
            }
        }
        unset($var);

        $data = array(
            'businessKey' => $this->sugarBean->id,
        );
        if (!empty($variables)) {
            $data['variables'] = $variables;
        }
        return SugarCamunda::getJsonClient()->post("/process-definition/{$this->processDefinition['id']}/submit-form", $data);
    }

    public function loadTask(&$task)
    {
        $camundaTask = $this->getCamundaTask($task);

        $user = $camundaTask->fetchAssignedUser();
        if (!empty($user)) {
            $full_name = $GLOBALS['locale']->getLocaleFormattedName($user['first_name'], $user['last_name']);
            $task['assignee_id'] = $user['id'];
            $task['assignee_full_name'] = $full_name;
        }
        if (!empty($task['created'])) {
            $task['created_inuserformat'] = CamundaForm::dateTimeToUserFormat($task['created']);
        }

        $task['form'] = $camundaTask->loadForm();
        $task['form']['variables'] = SugarCamunda::getJsonClient()->get("/task/{$task['id']}/form-variables");
        $users = $camundaTask->getCandidateUsers();
        $task['identity']['candidate_users'] = CamundaForm::getUsersAsOptions($users);
        $task['canSave'] = $camundaTask->saveAccess();
        $task['canAssign'] = $camundaTask->assignAccess();

        //user-operation history will be empty if rest api used with no auth
        $task['history'] = SugarCamunda::getJsonClient()->get('/history/user-operation', array(
            'taskId' => $task['id'],
            'sortBy' => 'timestamp',
            'sortOrder' => 'asc',
        ));
        CamundaForm::formatTaskHistory($task['history'], array(
            'userIdField' => $camundaTask->userIdField,
        ));
    }

    public function loadHistoryProcessInstance(&$processInstance)
    {
        $processInstance['startTime_inuserformat'] = CamundaForm::dateTimeToUserFormat($processInstance['startTime']);
        $processInstance['endTime_inuserformat'] = CamundaForm::dateTimeToUserFormat($processInstance['endTime']);
        $processInstance['variables'] = SugarCamunda::getJsonClient()->get("/history/variable-instance", array(
            'processInstanceId' => $processInstance['id'],
            'sortBy' => 'variableName',
            'sortOrder' => 'desc',
        ));
        $this->translateVariables($processInstance['variables'], $processInstance['processDefinitionId']);
        $processInstance['history'] = $this->getHistoryActivityInstances($processInstance['id']);
    }

    public function getHistoryActivityInstances($processInstanceId)
    {
        $history = SugarCamunda::getJsonClient()->get("/history/activity-instance", array(
            'processInstanceId' => $processInstanceId,
            'sortBy' => 'occurrence',
            'sortOrder' => 'asc',
        ));
        $tasksHistory = SugarCamunda::getJsonClient()->get("/history/task", array( //for additional properties (owner, etc)
            'processInstanceId' => $processInstanceId,
        ));
        foreach ($history as $key => &$activity) {
            if (empty($activity['activityName'])) {
                unset($history[$key]);
                continue;
            }
            if (!empty($activity['taskId'])) {
                foreach ($tasksHistory as $task) {
                    if ($task['id'] === $activity['taskId']) {
                        unset($task['id']);
                        $activity = array_merge($activity, $task);
                        break;
                    }
                }
            }
            if (!empty($activity['startTime'])) {
                $activity['startTime_inuserformat'] = CamundaForm::dateTimeToUserFormat($activity['startTime']);
            }
            if (!empty($activity['endTime'])) {
                $activity['endTime_inuserformat'] = CamundaForm::dateTimeToUserFormat($activity['endTime']);
                if (!empty($activity['owner'])) {
                    $activity['owner_id'] = CamundaForm::getUserId($activity['owner'], $this->userIdField);
                    $activity['owner_full_name'] = CamundaForm::getUserFullName($activity['owner'], $this->userIdField);
                }
                elseif (!empty($activity['assignee'])) {
                    $activity['assignee_id'] = CamundaForm::getUserId($activity['assignee'], $this->userIdField);
                    $activity['assignee_full_name'] = CamundaForm::getUserFullName($activity['assignee'], $this->userIdField);
                }
            }
            if ($activity['activityType'] == 'serviceTask' && empty($activity['canceled'])) {
                $activity['externalTasks'] = SugarCamunda::getJsonClient()->get("/external-task", array(
                    'activityId' => $activity['activityId'],
                    'processInstanceId' => $processInstanceId,
                    'sortBy' => 'id',
                    'sortOrder' => 'asc',
                ));
            }
        }
        unset($activity);
        return $history;
    }

    public function translateVariables(&$variables, $processDefinitionId)
    {
        CamundaForm::translateVariables($variables, $processDefinitionId);
    }

    public function runUrgentExternalTasks()
    {
        return array();
    }
}
