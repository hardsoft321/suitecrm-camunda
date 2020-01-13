<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'include/ListView/ListViewSmarty.php';
require_once 'modules/CamundaTaskSearch/CamundaTaskListViewData.php';

class CamundaTaskListViewSmarty extends ListViewSmarty
{
    function __construct()
    {
        $this->lvd = new CamundaTaskListViewData();
        $this->lvd->lv = $this;
        $this->multiSelect = false;
        $this->searchColumns = array();
        $this->ss = new Sugar_Smarty();
    }
}
