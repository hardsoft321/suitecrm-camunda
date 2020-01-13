<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'modules/CamundaProcesses/SugarCamunda.php';

//TODO: cache them all

class CamundaProcessDefinition
{
    public static function getById($id)
    {
        return SugarCamunda::getJsonClient()->get("/process-definition/{$id}");
    }

    public static function getBpmnXml($processDefinitionId)
    {
        $bpmn = SugarCamunda::getJsonClient()->get("/process-definition/{$processDefinitionId}/xml");
        return $bpmn['bpmn20Xml'];
    }

    public static function listKeyNames()
    {
        $sugarProcs = SugarCamunda::getSugarProcesses();
        $processDefinitions = SugarCamunda::getJsonClient()->get('/process-definition', array(
            'active' => true,
            'latestVersion' => true,
            'sortBy' => 'name',
            'sortOrder' => 'asc',
            'keysIn' => implode(',', array_keys($sugarProcs)),
        ));
        $names = array();
        foreach ($processDefinitions as $procDef) {
            $names[$procDef['key']] = $procDef['name'];
        }
        return $names;
    }

    public static function listTaskDefNames()
    {
        //TODO: should be not only latest but performance
        $processDefinitions = SugarCamunda::getJsonClient()->get('/process-definition', array(
            'active' => true,
            'latestVersion' => true,
            'sortBy' => 'name',
            'sortOrder' => 'asc',
        ));
        $names = array();
        foreach ($processDefinitions as $procDef) {
            $text = CamundaProcessDefinition::getBpmnXml($procDef['id']);
            $xml = simplexml_load_string($text);
            if (!empty($xml)) {
                $tasks = $xml->xpath('.//bpmn:userTask[@id and @name]');
                $taskNames = array();
                foreach ($tasks as $task) {
                    $taskNames[$procDef['key'].':'.(string)$task['id']] = (string)$task['name'] . " ({$procDef['name']})";
                }
                asort($taskNames);
                $names = array_merge($names, $taskNames);
            }
        }
        return $names;
    }
}
