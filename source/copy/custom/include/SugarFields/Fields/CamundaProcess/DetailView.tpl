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
<div class="camunda-panel" data-field="{{sugarvar key='name'}}">
</div>

<script>
$(function() {ldelim}
    var panel = $('.camunda-panel[data-field="{{sugarvar key='name'}}"]')
        .html("{{sugar_translate label='LBL_LOADING' module=''}}")
    $.get('index.php?module=CamundaProcesses&action=Panel', {ldelim}
          sugar_module: {$module|@json_encode}
        , sugar_record: {$id|@json_encode}
        , field: "{{sugarvar key='name'}}"
    {rdelim})
    .done(function(data) {ldelim}
        panel.html(data);
    {rdelim})
    .fail(function(data) {ldelim}
        panel.html('Error');
    {rdelim})

    $('[type="CamundaProcess"]')
    .removeClass('col-sm-10')
    .prev('.label').hide()
{rdelim})
</script>
