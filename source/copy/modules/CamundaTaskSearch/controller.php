<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'modules/CamundaProcesses/SugarCamunda.php';
require_once 'modules/CamundaProcesses/CamundaProcessDefinition.php';

class CamundaTaskSearchController extends SugarController
{
    public function loadBean()
    {
        $this->bean = BeanFactory::newBean('CamundaTaskSearch');
    }

    public function action_DetailView()
    {
        $this->view = '';
        $id = SugarCamunda::clean_string($_REQUEST['record']);
        if (!empty($id)) {
            $sugarProcs = SugarCamunda::getSugarProcesses();
            $task = SugarCamunda::getJsonClient()->get("/task/{$id}");
            $procDef = CamundaProcessDefinition::getById($task['processDefinitionId']);
            if (!empty($sugarProcs[$procDef['key']])) {
                $sugarProc = $sugarProcs[$procDef['key']];
                $procInst = SugarCamunda::getJsonClient()->get("/process-instance/{$task['processInstanceId']}");
                $this->set_redirect("index.php?module={$sugarProc['module']}&action=DetailView"
                    . "&return_field={$sugarProc['fieldName']}"
                    . "&record={$procInst['businessKey']}");
                $this->redirect();
                return;
            }
        }
        echo translate('ERROR_NO_RECORD');
    }
}
