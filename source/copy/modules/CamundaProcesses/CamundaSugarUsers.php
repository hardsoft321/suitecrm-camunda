<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */
class CamundaSugarUsers
{
    public function getUsers($groups, $roles, $additionalWhere = '')
    {
        if ((is_array($groups) && empty($groups)) || (is_array($roles) && empty($roles))) {
            return array();
        }
        $q = "SELECT DISTINCT users.* FROM users";
        if (!empty($groups)) {
            $q .= ", securitygroups, securitygroups_users";
        }
        if (!empty($roles)) {
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
        if (!empty($roles)) {
            // Роль через securitygroups здесь не проверяется,
            // т.е. роль должна быть привязана напрямую к пользователю
            $q .= " AND acl_roles_users.user_id = users.id
                AND acl_roles_users.role_id IN ('".implode("','", $roles)."')
                AND acl_roles_users.deleted = 0";
        }
        if ($additionalWhere) {
            $q .= " AND " . $additionalWhere;
        }
        $q .= " ORDER BY last_name, first_name";
        return self::getUsersBySql($q);
    }

    public static function getBeanGroups($bean, $searchUpper = true)
    {
        global $db;
        $cacheKey = $searchUpper ? 'allRecordGroups' : 'directRecordGroups';
        if(empty($bean->workflowData) || !isset($bean->workflowData[$cacheKey])) {
            if(empty($bean->workflowData)) {
                $bean->workflowData = array();
            }
            require_once('modules/SecurityGroups/SecurityGroup.php');
            $groupFocus = new SecurityGroup();
            if($searchUpper && method_exists($groupFocus, 'getAllRecordGroupsIds')) { //SecurityTeams321
                $bean->workflowData[$cacheKey] = $groupFocus->getAllRecordGroupsIds($bean->id, $bean->module_name);
            }
            else {
                $groups = array();
                $queryGroups = "SELECT securitygroup_id AS id FROM securitygroups_records WHERE record_id = '{$bean->id}' AND module = '{$bean->module_name}' AND deleted = 0";
                $res = $db->query($queryGroups);
                while($row = $db->fetchByAssoc($res)) {
                    $groups[$row['id']] = $row['id'];
                }
                $bean->workflowData[$cacheKey] = $groups;
            }
        }
        return $bean->workflowData[$cacheKey];
    }

    public static function getUsersBySql($sql)
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
