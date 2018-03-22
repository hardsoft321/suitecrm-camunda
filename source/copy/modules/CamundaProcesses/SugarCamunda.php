<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'modules/CamundaProcesses/UserList.php';

class SugarCamunda
{
    protected static $jsonClient;
    protected static $xmlClient;
    protected static $client;

    public static function getUrl()
    {
        return $GLOBALS['sugar_config']['camunda_url'];
    }

    public static function getEngineUrl()
    {
        return $GLOBALS['sugar_config']['camunda_engine_url'];
    }

    public static function getJsonClient()
    {
        if (!self::$jsonClient) {
            self::$jsonClient = new PestJSON(self::getEngineUrl());
        }
        return self::$jsonClient;
    }

    public static function getXmlClient()
    {
        if (!self::$xmlClient) {
            self::$xmlClient = new PestXML(self::getEngineUrl());
        }
        return self::$xmlClient;
    }

    public static function getClient()
    {
        if (!self::$client) {
            self::$client = new Pest(self::getEngineUrl());
        }
        return self::$client;
    }

    public static function loadProcessDefinitions($bean, $bpmnList = null)
    {
        $camunda = SugarCamunda::getJsonClient();
        $camundaRaw = SugarCamunda::getClient();

        $processDefinitions = $camunda->get('/process-definition', [
            'active' => true,
            'latestVersion' => true,
        ]);

        if ($bpmnList !== null) {
            foreach ($processDefinitions as $k => $processDefinition) {
                if (!in_array($processDefinition['resource'], $bpmnList)) {
                    unset($processDefinitions[$k]);
                }
            }
        }

        foreach ($processDefinitions as &$processDefinition) {
            $processDefinition['form'] = $camunda->get("process-definition/{$processDefinition['id']}/startForm");
            if ($processDefinition['form']) {
                if (strpos($processDefinition['form']['key'], "embedded:app:") === 0) {
                    $url = SugarCamunda::getUrl()
                        . str_replace("embedded:app:", $processDefinition['form']['contextPath'] . '/', $processDefinition['form']['key'])
                        . "?userId={$GLOBALS['current_user']->user_name}&noCache=".rand();
                    $processDefinition['form']['html'] = file_get_contents($url);
                }
                else {
                    $processDefinition['form']['html'] = $camundaRaw->get("/process-definition/{$processDefinition['id']}/rendered-form");
                }
            }
        }
        unset($processDefinition);

        return $processDefinitions;
    }

    public static function loadProcessInstances($bean, $bpmnList)
    {
        $camunda = SugarCamunda::getJsonClient();
        $camundaRaw = SugarCamunda::getClient();

        $processInstances = $camunda->get('/process-instance', [
            'businessKey' => $bean->id,
            //TODO: filter by processDefinitionId also
        ]);

        foreach ($processInstances as &$processInstance) {
            $processInstance['processDefinition'] = $camunda->get("/process-definition/{$processInstance['definitionId']}");

            $processInstance['variables'] = $camunda->get("/process-instance/{$processInstance['id']}/variables");
            self::translateVariables($processInstance['variables'], $processInstance['processDefinition']['id']);

            $processInstance['tasks'] = $camunda->get('task', [
                'processInstanceId' => $processInstance['id'],
            ]);
            foreach ($processInstance['tasks'] as &$task) {

                if (!empty($task['assignee'])) {
                    $user = $GLOBALS['db']->fetchOne("SELECT id, first_name, last_name FROM users WHERE user_name = "
                        .$GLOBALS['db']->quoted($task['assignee'])." AND deleted = 0");
                    if (!empty($user)) {
                        $full_name = $GLOBALS['locale']->getLocaleFormattedName($user['first_name'], $user['last_name']);
                        $task['assignee_id'] = $user['id'];
                        $task['assignee_full_name'] = $full_name;
                    }
                }
                if (!empty($task['created'])) {
                    $task['created_inuserformat'] = self::dateTimeToUserFormat($task['created']);
                }

                if ($task['formKey']) {
                    $task['form'] = $camunda->get("/task/{$task['id']}/form");
                    if (strpos($task['form']['key'], "embedded:app:") === 0) {
                        $url = SugarCamunda::getUrl()
                            . str_replace("embedded:app:", $task['form']['contextPath'] . '/', $task['form']['key'])
                            . "?taskId={$task['id']}&userId={$GLOBALS['current_user']->user_name}&noCache=".rand();
                        $task['form']['html'] = file_get_contents($url);
                    }
                }
                else {
                    try {
                        $task['form']['html'] = $camundaRaw->get("/task/{$task['id']}/rendered-form");
                    }
                    catch (Exception $ex) {
                    }
                }
                //TODO: make datepicker works
                $task['form']['variables'] = $camunda->get("/task/{$task['id']}/form-variables");

                $roles = self::getTaskCandidateRoles($task['id']);
                $users = empty($roles)
                    ? UserList::getAllGroupUsers($bean)
                    : UserList::getGroupUsersWithRoles($bean, $roles);
                $usersOptions = ['' => ''];
                foreach ($users as $user) {
                    $full_name = $GLOBALS['locale']->getLocaleFormattedName($user->first_name, $user->last_name);
                    $usersOptions[$user->id] = $full_name;
                }
                $task['identity']['all_group_users'] = $usersOptions;

                $task['canSave'] = self::canSaveTask(!empty($task['assignee_id']) ? $task['assignee_id'] : null);
                $task['canAssign'] = self::canAssignTask($bean, $users);

                $task['history'] = $camunda->get('/history/user-operation', [
                    'taskId' => $task['id'],
                ]);
                /* TODO: Нет истории смены ответственного. Так как нет нужных пользователей в камунде или нет логина? */
            }
            unset($task);

            $processInstance['history'] = self::getHistoryActivityInstances($processInstance['id']);
        }
        unset($processInstance);

        return $processInstances;
    }

    //TODO: refactor to OOP CamundaProcess, CamundaTask

    public static function canSaveTask($assigneeId)
    {
        return $GLOBALS['current_user']->isAdmin()
            || $assigneeId === $GLOBALS['current_user']->id;
    }

    public static function canAssignTask($bean, $roleUsers)
    {
        return $GLOBALS['current_user']->isAdmin()
            || $bean->isOwner($GLOBALS['current_user']->id)
            || isset($roleUsers[$GLOBALS['current_user']->id]);
    }

    public static function getTaskCandidateRoles($taskId)
    {
        global $db;
        $camunda = SugarCamunda::getJsonClient();
        $identityLinks = $camunda->get("/task/{$taskId}/identity-links", [
            'type' => 'candidate',
        ]);
        $roles = [];
        foreach ($identityLinks as $identity) {
            if (empty($identity['groupId'])) {
                continue;
            }
            $names = explode(',', $identity['groupId']);
            foreach ($names as $name) {
                $roleId = $db->getOne("SELECT id FROM acl_roles WHERE name = "
                    .$db->quoted(trim($name))." AND deleted = 0");
                $roles[] = $roleId;
            }
        }
        return $roles;
    }

    public static function loadHistoryProcessInstances($bean, $bpmnList = null)
    {
        $camunda = SugarCamunda::getJsonClient();

        $historyProcessInstances = $camunda->get('/history/process-instance', [
            'processInstanceBusinessKey' => $bean->id,
            'finished' => true,
        ]);

        foreach ($historyProcessInstances as &$processInstance) {
            $processInstance['startTime_inuserformat'] = self::dateTimeToUserFormat($processInstance['startTime']);
            $processInstance['endTime_inuserformat'] = self::dateTimeToUserFormat($processInstance['endTime']);
            $processInstance['processDefinition'] = $camunda->get("/process-definition/{$processInstance['processDefinitionId']}");
            $processInstance['variables'] = $camunda->get("/history/variable-instance", [
                'processInstanceId' => $processInstance['id'],
            ]);
            self::translateVariables($processInstance['variables'], $processInstance['processDefinitionId']);
            $processInstance['history'] = self::getHistoryActivityInstances($processInstance['id']);
        }
        unset($processInstance);

        return $historyProcessInstances;
    }

    public static function getHistoryActivityInstances($processInstanceId)
    {
        $camunda = SugarCamunda::getJsonClient();
        $history = $camunda->get("/history/activity-instance", [
            'processInstanceId' => $processInstanceId,
            'sortBy' => 'occurrence',
            'sortOrder' => 'asc',
        ]);
        foreach ($history as $key => &$activity) {
            if (empty($activity['activityName'])) {
                unset($history[$key]);
                continue;
            }
            if (!empty($activity['startTime'])) {
                $activity['startTime_inuserformat'] = self::dateTimeToUserFormat($activity['startTime']);
            }
            if (!empty($activity['endTime'])) {
                $activity['endTime_inuserformat'] = self::dateTimeToUserFormat($activity['endTime']);
            }
        }
        unset($activity);
        return $history;
    }

    public static function dateTimeToUserFormat($datetime)
    {
        $time = strtotime($datetime);
        return $GLOBALS['timedate']->to_display_date_time(
            date('Y-m-d H:i:s', $time), true, true, $GLOBALS['current_user']);
    }

    public static function translateVariables(&$variables, $processDefinitionId)
    {
        foreach ($variables as $key => &$variable) {
            $varName = !empty($variable['name']) ? $variable['name'] : $key;
            if ($varName == '_dummy') { // see action_CompleteTask
                unset($variables[$key]);
            }
            $variable['field'] = $varName;
            $variable['label'] = self::getFormFieldLabel($varName, $processDefinitionId);
            $variable['value_inuserformat'] = $variable['value'];
            if (!empty($variable['value'])) {
                if ($variable['type'] == 'Date') {
                    $variable['value_inuserformat'] = self::dateTimeToUserFormat($variable['value']);
                }
                else {
                    $variable['value_inuserformat'] = self::getFormFieldLabel($varName, $processDefinitionId, $variable['value']);
                }
            }
        }
        unset($variable);
    }

    public static function getFormFieldLabel($formField, $processDefinitionId, $selectedValue = null)
    {
        static $bpmnStrings;
        static $bpmnListStrings;
        if (!isset($bpmnStrings[$processDefinitionId])) {
            //TODO: store to cache dir too
            $bpmnStrings[$processDefinitionId] = [];
            $bpmnListStrings[$processDefinitionId] = [];
            $text = self::getBpmnXml($processDefinitionId);
            $xml = simplexml_load_string($text);
            if (!empty($xml)) {
                $fields = $xml->xpath('.//camunda:formField[@id and @label]');
                foreach ($fields as $field) {
                    $bpmnStrings[$processDefinitionId][(string)$field['id']] = (string)$field['label'];
                    if ($field['type'] == 'enum') {
                        $values = $field->xpath('./camunda:value[@id and @name]');
                        foreach ($values as $value) {
                            $bpmnListStrings[$processDefinitionId] [(string)$field['id']]
                                [(string)$value['id']] = (string)$value['name'];
                        }
                    }
                }
            }
        }
        if ($selectedValue !== null) {
            return isset($bpmnListStrings[$processDefinitionId][$formField][$selectedValue])
                ? $bpmnListStrings[$processDefinitionId][$formField][$selectedValue]
                : $selectedValue;
        }
        return $bpmnStrings[$processDefinitionId][$formField];
    }

    public static function getBpmnXmlByTaskId($taskId)
    {
        $camunda = SugarCamunda::getJsonClient();
        $task = $camunda->get("/task/{$taskId}");
        return self::getBpmnXml($task['processDefinitionId']);
    }

    public static function getBpmnXml($processDefinitionId)
    {
        $camunda = SugarCamunda::getJsonClient();
        $bpmn = $camunda->get("/process-definition/{$processDefinitionId}/xml");
        return $bpmn['bpmn20Xml'];
    }
}
