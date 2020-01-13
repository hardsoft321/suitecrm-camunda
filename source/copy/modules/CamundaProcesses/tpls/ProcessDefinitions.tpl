{**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 *}

<link rel="stylesheet" href="{sugar_getjspath file='modules/CamundaProcesses/tpls/ProcessInstances.css'}" />

<table class="row camunda-process-definitions">
{foreach from=$processDefinitions item="processDefinition"}
<tr data-procdefkey="{$processInstance.processDefinition.key}">
    <td scope="col" width="12.5%">
        <label class="process-name" data-key="{$processDefinition.key}">{$processDefinition.name}</label>
        {if $current_user_is_admin}
        <div class="admin-only">
            {sugar_translate label='LBL_VERSION' module='CamundaProcesses'}: {$processDefinition.version}
        </div>
        {/if}
    </td>
    <td>
    <div class="camunda-process-definition detail-view-field" data-id="{$processDefinition.id}">
        {$processDefinition.form.html}
<script>
var defId = {$processDefinition.id|@json_encode};
addForm(defId);
$('.camunda-process-definition[data-id="'+defId+'"] form')
.attr('method', 'POST').attr('action', 'index.php').attr('name', defId).attr('id', defId)
.addClass('camunda-sugar-form').addClass('camunda-procdef-form').append(
'<div class="form-group submit-group">\
    <input type="hidden" name="module" value="CamundaProcesses" />\
    <input type="hidden" name="action" value="StartProcess" />\
    <input type="hidden" name="sugar_module" value="{$bean->module_name}" />\
    <input type="hidden" name="sugar_record" value="{$bean->id}" />\
    <input type="hidden" name="camunda_field_name" value="{$camunda_field_name}" />\
    <input type="hidden" name="definition_id" value="'+defId+'" />\
    <div class="overall_validation"><input type="hidden" name="overall_validation" value="1" /></div>\
    <input type="submit" class="button primary" value="{sugar_translate label='LBL_START_PROCESS' module='CamundaProcesses'}" \
        {if empty($processDefinition.startAccess)} disabled="disabled"{/if} \
        onclick="var _form = document.getElementById(\'' + defId+ '\');\
            if(check_form(\'' + defId + '\'))\
                SUGAR.ajaxUI.submitForm(_form);\
            return false;"\
    />\
</div>')
.attr('data-sugared', 'true')
</script>
    </div>
    </td>
</tr>
{/foreach}
</table>
