{**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 *}

<style>
.detail-view-field[type="CamundaProcess"] {ldelim}
    background-color: inherit;
{rdelim}
.camunda-panel .detail-view-field {ldelim}
    display: block;
{rdelim}
.camunda-panel .detail-view-field + .detail-view-field {ldelim}
    margin-top: 15px;
{rdelim}
</style>


<div class="sugar_field camunda-panel" id="{{sugarvar key='name'}}" data-field="{{sugarvar key='name'}}"
    data-sugar_module="{$module}" data-sugar_record="{$id}">
    <a href="#" class="load-camunda-panel" onclick="loadCamundaPanel('{{sugarvar key='name'}}'); return false">
        {sugar_translate label='LBL_LOAD' module='CamundaProcesses'}
    </a>
</div>

<script>
{literal}
function loadCamundaPanel(panelName) {
    var panel = $('.camunda-panel[data-field="' + panelName + '"]')
    if (panel.find('.load-camunda-panel').length == 0) {
        return;
    }
    panel.html("{{sugar_translate label='LBL_LOADING' module=''}}")
    $.get('index.php?module=CamundaProcesses&action=Panel', {
          sugar_module: panel.attr('data-sugar_module')
        , sugar_record: panel.attr('data-sugar_record')
        , camunda_field_name: panelName
    })
    .done(function(data) {
        panel.html(data);
    })
    .fail(function(data) {
        panel.html('Error');
    })

    $('[type="CamundaProcess"]')
    .removeClass('col-sm-10')
    .prev('.label').hide()
}
{/literal}

$(function() {ldelim}
setTimeout(function(){ldelim}
    var panelName = "{{sugarvar key='name'}}";
    var returnField = {$smarty.request.return_field|@json_encode};
{literal}
    var panel = $('.camunda-panel[data-field="' + panelName + '"]')
    var sugarPanel = panel.closest('.detail.view')
    if (panelName === returnField) {
        sugarPanel.removeClass('collapsed').addClass('expanded')
    }
    if (sugarPanel.hasClass('expanded')) {
        loadCamundaPanel(panelName);
    }
    else if (sugarPanel.hasClass('collapsed')) {
        sugarPanel.find('.expandLink').click(function() {
            loadCamundaPanel(panelName);
        })
    }
{/literal}
{rdelim}, 100);
{rdelim})
</script>
