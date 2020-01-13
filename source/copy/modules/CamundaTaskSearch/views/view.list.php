<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'include/MVC/View/views/view.list.php';
require_once 'modules/CamundaTaskSearch/CamundaTaskListViewSmarty.php';


class CamundaTaskSearchViewList extends ViewList
{
    function preDisplay()
    {
        $this->lv = new CamundaTaskListViewSmarty();
    }

    function prepareSearchForm()
    {
        parent::prepareSearchForm();
        $this->searchForm->lv->searchForm = $this->searchForm;
    }
}
