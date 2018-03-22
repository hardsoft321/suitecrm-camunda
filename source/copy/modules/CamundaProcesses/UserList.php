<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 *
 * Все в роли и в группе
 *
 * Эта функция используется по умолчанию.
 * Возвращает список пользователей, находящихся в тех же группах, что и запись,
 * включая их родительские группы (вплоть до корневой группы), а также обладающих
 * ролью, настроенной в поле "Роль" данного статуса.
 * Роль через securitygroups здесь не проверяется, т.е. роль должна быть привязана
 * напрямую к пользователю.
 * Пользователи со статусом "Не активен" или не "Display Employee Record" игнорируются.
 */
class UserList {

    public $statusRoleField = 'role_id';
    public $additionalWhere = '';

    public static function getAllGroupUsers($bean)
    {
        $userList = new UserList();
        $userList->status_data[$userList->statusRoleField] = false;
        return $userList->getList($bean);
    }

    public static function getGroupUsersWithRoles($bean, $roles)
    {
        $userList = new UserList();
        $userList->status_data[$userList->statusRoleField] = $roles;
        return $userList->getList($bean);
    }

    public function getList($bean)
    {
        $groups = $this->getBeanGroups($bean);
        $roles = $this->status_data[$this->statusRoleField];
        $q = "SELECT DISTINCT users.* FROM users";
        if (!empty($groups)) {
            $q .= ", securitygroups, securitygroups_users";
        }
        if ($roles !== false) {
            $q .= ", acl_roles_users";
        }
        $q .= " WHERE users.status != 'Inactive'
            AND show_on_employees = 1
            AND users.deleted = 0";
        if (!empty($groups)) {
            $q .= " AND securitygroups.id IN ('".implode("','", $groups)."')
                AND securitygroups.id = securitygroups_users.securitygroup_id AND securitygroups_users.user_id = users.id
                AND securitygroups.deleted = 0 AND securitygroups_users.deleted = 0";
        }
        if ($roles !== false) {
            // Роль через securitygroups здесь не проверяется,
            // т.е. роль должна быть привязана напрямую к пользователю
            $q .= " AND acl_roles_users.user_id = users.id
                AND acl_roles_users.role_id IN ('".implode("','", $roles)."')
                AND acl_roles_users.deleted = 0";
        }
        if($this->additionalWhere) {
            $q .= " AND ".$this->additionalWhere;
        }
        $q .= " ORDER BY last_name, first_name";
        return $this->getUsersBySql($q);
    }

    protected function getBeanGroups($bean)
    {
        global $db;
        if(empty($bean->workflowData) || !isset($bean->workflowData['allRecordGroups'])) {
            if(empty($bean->workflowData)) {
                $bean->workflowData = array();
            }
            require_once('modules/SecurityGroups/SecurityGroup.php');
            $groupFocus = new SecurityGroup();
            if(method_exists($groupFocus, 'getAllRecordGroupsIds')) { //SecurityTeams321
                $bean->workflowData['allRecordGroups'] = $groupFocus->getAllRecordGroupsIds($bean->id, $bean->module_name);
            }
            else {
                $groups = array();
                $queryGroups = "SELECT securitygroup_id AS id FROM securitygroups_records WHERE record_id = '{$bean->id}' AND module = '{$bean->module_name}' AND deleted = 0";
                $res = $db->query($queryGroups);
                while($row = $db->fetchByAssoc($res)) {
                    $groups[$row['id']] = $row['id'];
                }
                $bean->workflowData['allRecordGroups'] = $groups;
            }
        }
        return $bean->workflowData['allRecordGroups'];
    }

    protected function getUsersBySql($sql)
    {
        global $db;
        $qr = $db->query($sql);
        $users = array();
        while($row = $db->fetchByAssoc($qr)) {
            $user = BeanFactory::newBean('Users');
            $user->populateFromRow($row);
            $users[$user->id] = $user;
        }
        return $users;
    }
}
