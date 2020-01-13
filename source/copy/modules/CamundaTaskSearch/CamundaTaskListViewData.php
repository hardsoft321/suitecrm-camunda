<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'include/ListView/ListViewData.php';
require_once 'modules/CamundaProcesses/SugarCamunda.php';
require_once 'modules/CamundaProcesses/CamundaProcessDefinition.php';
require_once 'modules/CamundaProcesses/CamundaForm.php';

class CamundaTaskListViewData extends ListViewData
{
    function getListViewData($seed, $where, $offset=-1, $limit = -1, $filter_fields=array(),$params=array(),$id_field = 'id',$singleSelect=true)
    {
        $this->setVariableName($seed->object_name, $where, $this->listviewName);
        $totalCounted = empty($GLOBALS['sugar_config']['disable_count_query']);

        $camundaParams = array();
        if (!empty($this->lv->searchForm->searchFields['taskDefinitionKey']['value'])) {
            $keys = array();
            foreach ($this->lv->searchForm->searchFields['taskDefinitionKey']['value'] as $key) {
                list($procDefKey, $taskDefKey) = explode(':', $key);
                $this->lv->searchForm->searchFields['processDefinitionKey']['value'][] = $procDefKey;
                $keys[] = $taskDefKey;
            }
            $camundaParams['taskDefinitionKeyIn'] = implode(',', $keys);
        }
        if (!empty($this->lv->searchForm->searchFields['processDefinitionKey']['value'])) {
            $camundaParams['processDefinitionKeyIn'] = implode(',', $this->lv->searchForm->searchFields['processDefinitionKey']['value']);
        }
        if (!empty($this->lv->searchForm->searchFields['current_user_only']['value'])) {
            $camundaParams['assignee'] = $GLOBALS['current_user']->id;
        }
        elseif (!empty($this->lv->searchForm->searchFields['assignee']['value'])) {
            $userId = reset($this->lv->searchForm->searchFields['assignee']['value']);
            if (!empty($userId)) {
                $camundaParams['assignee'] = $userId;
            }
        }
        if (empty($camundaParams['processDefinitionKeyIn'])) {
            $sugarProcs = SugarCamunda::getSugarProcesses();
            $camundaParams['processDefinitionKeyIn'] = implode(',', array_keys($sugarProcs));
        }

        // if(!empty($params['overrideOrder']) && !empty($params['orderBy'])) {
        if (!empty($_POST['orderBy']) && !empty($params['orderBy'])) {
            $order = $this->getOrderBy(strtolower($params['orderBy']), (empty($params['sortOrder']) ? '' : $params['sortOrder'])); // retreive from $_REQUEST
        }
        else {
            $order = $this->getOrderBy(); // retreive from $_REQUEST
        }
        if(empty($order['orderBy']) && !empty($params['orderBy'])) {
            $order['orderBy'] = $params['orderBy'];
            $order['sortOrder'] = empty($params['sortOrder']) ? '' : $params['sortOrder'];
        }
        if (empty($order['sortOrder'])) {
            $order['sortOrder'] = 'ASC';
        }

        if ($order['orderBy'] === 'taskdefinitionname') {
            $camundaParams['sortBy'] = 'nameCaseInsensitive';
        }
        elseif ($order['orderBy'] === 'date_entered') {
            $camundaParams['sortBy'] = 'created';
        }
        elseif ($order['orderBy'] === 'assignee') {
            $camundaParams['sortBy'] = 'assignee';
        }
        if (!empty($camundaParams['sortBy'])) {
            $camundaParams['sortOrder'] = strtolower($order['sortOrder']);
        }

        if ($limit >= -1) {
            if ($limit == -1) {
                $limit = $this->getLimit();
            }
            $dyn_offset = $this->getOffset();
            if ($dyn_offset > 0 || !is_int($dyn_offset)) {
                $offset = $dyn_offset;
            }
            if (strcmp($offset, 'end') == 0) {
                $totalCount = $this->getTotalCount($camundaParams);
                $offset = (floor(($totalCount -1) / $limit)) * $limit;
            }
            $camundaParams['firstResult'] = $offset;
            $camundaParams['maxResults'] = $limit + 1;
        }

        $data = SugarCamunda::getJsonClient()->get("/task", $camundaParams ? $camundaParams : null);

        $sugarProcs = !empty($data) ? SugarCamunda::getSugarProcesses() : array();
        $rows = array();
        $count = 0;
        $idIndex = array();
        $tag = array();
        foreach ($data as $row) {
            $count++;
            if ($count > $limit && $limit >= -1) {
                break;
            }
            $i = $count - 1;
            $row['ID'] = $row['id'];
            $procDef = null;
            if (!empty($row['processDefinitionId'])) {
                $procDef = CamundaProcessDefinition::getById($row['processDefinitionId']);
                $row['PROCESSDEFINITIONKEY'] = $procDef['key'];
                $row['PROCESSDEFINITIONNAME'] = $procDef['name'];
            }
            $row['TASKDEFINITIONKEY'] = $row['taskDefinitionKey'];
            $row['TASKDEFINITIONNAME'] = $row['name'];
            $row['DATE_ENTERED'] = !empty($row['created']) ? CamundaForm::dateTimeToUserFormat($row['created']) : '';
            $row['ASSIGNEE'] = $row['assignee'];
            if (!empty($row['assignee'])) {
                $field = 'id'; //TODO: depends on process
                $row['ASSIGNEENAME'] = CamundaForm::getUserFullName($row['assignee'], $field);
                $row['ASSIGNEEID'] = $row['assignee'];
            }

            $bean = null;
            if ($procDef && !empty($row['processInstanceId']) && !empty($sugarProcs[$procDef['key']])) {
                $sugarProc = $sugarProcs[$procDef['key']];
                $procInst = SugarCamunda::getJsonClient()->get("/process-instance/{$row['processInstanceId']}");
                $bean = BeanFactory::getBean($sugarProc['module'], $procInst['businessKey']);
                $row['BUSINESSNAME'] = $bean ? $bean->get_summary_text() : '-';
            }

            $idIndex[$row['id']] = array($i);
            $tag[$i] = array(
                'MAIN' => $bean ? 'a' : 'span',
            );
            $rows[] = $row;
        }

        $nextOffset = -1;
        $prevOffset = -1;
        $endOffset = -1;
        if($count > $limit) {
            $nextOffset = $offset + $limit;
        }

        if($offset > 0) {
            $prevOffset = $offset - $limit;
            if($prevOffset < 0)$prevOffset = 0;
        }
        $totalCount = $count + $offset;

        if ($count >= $limit && $totalCounted) {
            $totalCount = $this->getTotalCount($camundaParams);
        }
        $endOffset = (floor(($totalCount - 1) / $limit)) * $limit;

        $_GET['ajax_load'] = $_POST['ajax_load'] = '0'; //disable ajaxUI for paginationChangeButtons
        $pageData = array();
        $pageData['ordering'] = $order;
        $pageData['ordering']['sortOrder'] = $this->getReverseSortOrder($pageData['ordering']['sortOrder']);
        $pageData['queries'] = $this->generateQueries($pageData['ordering']['sortOrder'], $offset, $prevOffset, $nextOffset,  $endOffset, $totalCounted);
        $pageData['urls'] = $this->generateURLS($pageData['queries']);
        $pageData['offsets'] = array('current'=>$offset, 'next'=>$nextOffset, 'prev'=>$prevOffset, 'end'=>$endOffset, 'total'=>$totalCount, 'totalCounted'=>$totalCounted);
        $pageData['bean'] = array('objectName' => $seed->object_name, 'moduleDir' => $seed->module_dir, 'moduleName' => $seed->module_dir);
        $pageData['stamp'] = $this->stamp;
        $pageData['access'] = array('view' => $seed->ACLAccess('DetailView'), 'edit' => $seed->ACLAccess('EditView'));
        $pageData['idIndex'] = $idIndex;
        $pageData['tag'] = $tag;

        $lvData = array(
            'data' => $rows,
            'query' => '',
            'pageData' => $pageData,
        );
        return $lvData;
    }

    public function getTotalCount($params)
    {
        $dataCount = SugarCamunda::getJsonClient()->get("/task/count", $params ? $params : null);
        return $dataCount['count'];
    }
}
