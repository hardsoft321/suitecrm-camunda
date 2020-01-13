<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

class CamundaTaskSearch extends Basic
{
    public $module_dir = 'CamundaTaskSearch';
    public $object_name = 'CamundaTaskSearch';
    public $table_name = '';

    public function ACLAccess($view, $is_owner='not_set', $in_group='not_set')
    {
        $view = strtolower($view);
        if ($view === 'editview') {
            return false;
        }
        return true;
    }
}
