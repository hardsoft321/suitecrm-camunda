<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'modules/CamundaProcesses/SugarCamunda.php';
require_once 'modules/CamundaProcesses/CamundaForm.php';
require_once 'modules/CamundaProcesses/CamundaSugarUsers.php';

class CamundaTask
{
    public $candidateGroupsField = 'name';
    public $userIdField = 'id';
    public $props;
    public $sugarBean;
    public $defaultAccess = true;
    public $useGroupFilter = true;

    public function __construct($props, $bean)
    {
        $this->props = $props;
        $this->sugarBean = $bean;
    }

    public function saveAccess()
    {
        $user = $GLOBALS['current_user'];
        return $user->isAdmin() || $this->defaultAccess;
    }

    public function assignAccess()
    {
        $user = $GLOBALS['current_user'];
        return $user->isAdmin() || $this->defaultAccess;
    }

    public function getCandidateUsers()
    {
        if (!isset($this->_candidateUsers)) {
            $roleNameList = $this->getCamundaCandidateGroups();
            $roleIdList = array();
            foreach ($roleNameList as $name) {
                $roleIdList[] = $this->getRoleId($name);
            }
            $this->_candidateUsers = CamundaSugarUsers::getUsers(
                $this->useGroupFilter ? CamundaSugarUsers::getBeanGroups($this->sugarBean) : null
                , !empty($roleIdList) ? $roleIdList : null);
        }
        return $this->_candidateUsers;
    }

    public function canBeAssigned($user)
    {
        $candidates = $this->getCandidateUsers();
        return !empty($candidates[$user->id]);
    }

    public function assign($user)
    {
        $userId = $user ? $user->{$this->userIdField} : null;
        if ((empty($this->props['assignee']) && empty($user)) || $this->props['assignee'] === $userId) {
            return;
        }
        SugarCamunda::getJsonClient()->post("/task/{$this->props['id']}/assignee", array(
            'userId' => $userId,
        ));
    }

    public function setOwner($user)
    {
        $userId = $user ? $user->{$this->userIdField} : null;
        if ((empty($this->props['owner']) && empty($user)) || $this->props['owner'] === $userId) {
            return;
        }
        SugarCamunda::getJsonClient()->put("/task/{$this->props['id']}/", array(
            'owner' => $userId,
        ));
    }

    public function submitForm($variables)
    {
        SugarCamunda::getJsonClient()->post("/task/{$this->props['id']}/submit-form", empty($variables) ? new stdClass() : array(
            'variables' => $variables,
        ));
    }

    public function fetchAssignedUser()
    {
        if (empty($this->props['assignee'])) {
            return null;
        }
        return $GLOBALS['db']->fetchOne("SELECT id, first_name, last_name FROM users WHERE {$this->userIdField} = "
            .$GLOBALS['db']->quoted($this->props['assignee'])." AND deleted = 0");
    }

    public function getFormVariables()
    {
        $form = $this->loadForm();
        return CamundaForm::parseFormVariables($form['html']);
    }

    public function loadForm()
    {
        $form = SugarCamunda::getJsonClient()->get("/task/{$this->props['id']}/form");
        if (strpos($form['key'], "embedded:app:") === 0) {
            $url = SugarCamunda::getUrl()
                . str_replace("embedded:app:", $form['contextPath'] . '/', $form['key'])
                ."?userId={$GLOBALS['current_user']->{$this->userIdField}}&noCache=".rand();
            $form['html'] = file_get_contents($url);
        }
        elseif (strpos($form['key'], "embedded:engine:") === 0) {
            $form['html'] = SugarCamunda::getClient()->get("/task/{$this->props['id']}/rendered-form");
        }
        else {
            $form['html'] = '<form></form>';
        }
        //TODO: make datepicker work
        return $form;
    }

    public function fillTaskVariables($unsafeSource, &$variables)
    {
        CamundaForm::fillVariables($unsafeSource, $variables);
    }

    public function getCamundaCandidateGroups()
    {
        if (!isset($this->camundaCandidateGroups)) { //camundaCandidateGroups can be set from outside
            $identityLinks = SugarCamunda::getJsonClient()->get("/task/{$this->props['id']}/identity-links", array(
                'type' => 'candidate',
            ));
            $groups = array();
            foreach ($identityLinks as $identity) {
                if (!empty($identity['groupId'])) {
                    $groups = array_merge($groups, explode(',', $identity['groupId']));
                }
            }
            $this->camundaCandidateGroups = $groups;
        }
        return $this->camundaCandidateGroups;
    }

    public function getRoleId($name)
    {
        return $GLOBALS['db']->getOne("SELECT id FROM acl_roles WHERE " .$this->candidateGroupsField . " = "
            . $GLOBALS['db']->quoted(trim($name))." AND deleted = 0");
    }
}
