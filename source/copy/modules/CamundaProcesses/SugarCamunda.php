<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'modules/CamundaProcesses/CamundaProcess.php';

class SugarCamunda
{
    protected static $jsonClient;
    protected static $xmlClient;
    protected static $client;

    public static function getUrl()
    {
        return $GLOBALS['sugar_config']['camunda']['url'];
    }

    public static function getEngineUrl()
    {
        return $GLOBALS['sugar_config']['camunda']['engine_url'];
    }

    public static function getJsonClient()
    {
        if (!self::$jsonClient) {
            self::$jsonClient = new PestJSON(self::getEngineUrl());
            if (!empty($GLOBALS['sugar_config']['camunda']['camunda_user'])) {
                self::$jsonClient->setupAuth($GLOBALS['sugar_config']['camunda']['camunda_user']['login']
                    , $GLOBALS['sugar_config']['camunda']['camunda_user']['password']);
            }
        }
        return self::$jsonClient;
    }

    public static function getClient()
    {
        if (!self::$client) {
            self::$client = new Pest(self::getEngineUrl());
            if (!empty($GLOBALS['sugar_config']['camunda']['camunda_user'])) {
                self::$client->setupAuth($GLOBALS['sugar_config']['camunda']['camunda_user']['login']
                    , $GLOBALS['sugar_config']['camunda']['camunda_user']['password']);
            }
        }
        return self::$client;
    }

    public static function getCamundaProcess($processDefinition, $bean, $fieldName)
    {
        if (empty($processDefinition['key']) && !empty($processDefinition['id'])) {
            if (preg_match('/(?P<key>[^:]+):(?P<version>\d+):(?P<uuid>.+)/', $processDefinition['id'], $matches)) {
                $processDefinition['key'] = $matches['key'];
            }
        }
        if (!empty($processDefinition['key'])) {
            $defKey = $processDefinition['key'];
            $processMap = !empty($bean->field_defs[$fieldName]['processes']) ? $bean->field_defs[$fieldName]['processes'] : null;
            if ($processMap === null || (isset($processMap[$defKey]) && $processMap[$defKey] === 'CamundaProcess')) {
                return new CamundaProcess($processDefinition, $bean, $fieldName);
            }
            if (!empty($processMap) && isset($processMap[$defKey]) && is_array($processMap[$defKey])) {
                require_once ($processMap[$defKey]['include']);
                return new $processMap[$defKey]['class']($processDefinition, $bean, $fieldName);
            }
        }
        return null;
    }

    public static function clean_string($str, $filter = "STANDARD", $dieOnBadData = true)
    {
        $filters = array(
            "STANDARD" => '#[^A-Z0-9\-_\.:]#i',
        );
        if (preg_match($filters[$filter], $str)) {
            if (isset($GLOBALS['log']) && is_object($GLOBALS['log'])) {
                $GLOBALS['log']->fatal("SECURITY[$filter]: bad data passed in; string: {$str}");
            }
            if ( $dieOnBadData ) {
                echo "Bad data passed in; <a href=\"index.php\">Return to Home</a>";
                sugar_die('');
            }
            return false;
        }
        else {
            return $str;
        }
    }

    public static function loadProcessDefinitions($bean, $fieldName)
    {
        $processDefinitions = SugarCamunda::getJsonClient()->get('/process-definition', array(
            'active' => true,
            'latestVersion' => true,
            'sortBy' => 'key',
            'sortOrder' => 'desc',
        ));
        foreach ($processDefinitions as $k => &$processDefinition) {
            $process = self::getCamundaProcess($processDefinition, $bean, $fieldName);
            if ($process) {
                $process->loadProcessDefinition($processDefinition);
            }
            else {
                unset($processDefinitions[$k]);
            }
        }
        unset($processDefinition);
        return $processDefinitions;
    }

    public static function loadProcessInstances($bean, $fieldName)
    {
        $processInstances = SugarCamunda::getJsonClient()->get('/process-instance', array(
            'businessKey' => $bean->id,
            'sortBy' => 'instanceId',
            'sortOrder' => 'asc',
        ));
        foreach ($processInstances as $k => &$processInstance) {
            $processInstance['processDefinition'] = SugarCamunda::getJsonClient()->get("/process-definition/{$processInstance['definitionId']}");
            $process = self::getCamundaProcess($processInstance['processDefinition'], $bean, $fieldName);
            if ($process) {
                $process->loadProcessInstance($processInstance);
                if ($processInstance == null) {
                    unset($processInstances[$k]);
                }
            }
            else {
                unset($processInstances[$k]);
            }
        }
        unset($processInstance);
        return $processInstances;
    }

    public static function loadHistoryProcessInstances($bean, $fieldName)
    {
        $historyProcessInstances = SugarCamunda::getJsonClient()->get('/history/process-instance', array(
            'processInstanceBusinessKey' => $bean->id,
            'finished' => true,
            'sortBy' => 'endTime',
            'sortOrder' => 'desc',
        ));

        foreach ($historyProcessInstances as $k => &$processInstance) {
            $processInstance['processDefinition'] = SugarCamunda::getJsonClient()->get("/process-definition/{$processInstance['processDefinitionId']}");
            $process = self::getCamundaProcess($processInstance['processDefinition'], $bean, $fieldName);
            if ($process) {
                $process->loadHistoryProcessInstance($processInstance);
            }
            else {
                unset($historyProcessInstances[$k]);
            }
        }
        unset($processInstance);

        return $historyProcessInstances;
    }

    public static function getSugarProcesses()
    {
        $sugarProcs = array();
        foreach ($GLOBALS['moduleList'] as $module) {
            $bean = BeanFactory::newBean($module);
            if ($bean && !empty($bean->field_defs)) {
                foreach ($bean->field_defs as $fieldDef) {
                    if (!empty($fieldDef['type']) && $fieldDef['type'] === 'CamundaProcess') {
                        foreach ($fieldDef['processes'] as $defKey => $proc) {
                            if (empty($sugarProcs[$defKey])) {
                                $sugarProcs[$defKey] = array(
                                    'processDefinitionKey' => $defKey,
                                    'module' => $module,
                                    'fieldName' => $fieldDef['name'],
                                );
                            }
                        }
                    }
                }
            }
        }
        return $sugarProcs;
    }
}
