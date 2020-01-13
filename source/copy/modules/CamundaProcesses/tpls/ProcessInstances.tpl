{**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 *}

<link rel="stylesheet" href="{sugar_getjspath file='modules/CamundaProcesses/tpls/ProcessInstances.css'}" />

<div class="camunda-process-instances">
{foreach from=$processInstances item="processInstance"}
<div class="camunda-process-instance" data-procdefkey="{$processInstance.processDefinition.key}">
    <h3>{sugar_translate label='LBL_PROCESS' module='CamundaProcesses'}: {$processInstance.processDefinition.name}</h3>

    {if $current_user_is_admin}
    <div class="admin-only">
        {sugar_translate label='LBL_VERSION' module='CamundaProcesses'}: {$processInstance.processDefinition.version}
        {if empty($processInstance.endTime)}
        <form method="POST" class="camunda-process-delete" action="index.php" onsubmit="return confirmProcessDeletion(event)">
            <input type="hidden" name="module" value="CamundaProcesses" />
            <input type="hidden" name="action" value="DeleteProcess" />
            <input type="hidden" name="sugar_module" value="{$bean->module_name}" />
            <input type="hidden" name="sugar_record" value="{$bean->id}" />
            <input type="hidden" name="camunda_field_name" value="{$camunda_field_name}" />
            <input type="hidden" name="process_id" value="{$processInstance.id}" />
            <input type="submit" name="delete" class="button primary"
                value="{sugar_translate label='LBL_DELETE_PROCESS' module='CamundaProcesses'}" />
        </form>
        <script>
        function confirmProcessDeletion(event) {ldelim}
            if (!confirm("{sugar_translate label='LBL_DELETE_PROCESS_CONFIRM' module='CamundaProcesses'}")) {ldelim}
                event.preventDefault();
                return false;
            {rdelim}
            return true;
        {rdelim}
        </script>
        {/if}
    </div>
    {/if}

    <div class="camunda-variables">
        <h4>{sugar_translate label='LBL_VARIABLES' module='CamundaProcesses'}</h4>
        <div class="detail-view-field">
        {if !empty($processInstance.startTime_inuserformat)}
        <div class="proc-var camunda-start-time form-group">
            <label>{sugar_translate label='LBL_START_TIME' module='CamundaProcesses'}:</label>
            <div>{$processInstance.startTime_inuserformat}</div>
        </div>
        {/if}
        {if !empty($processInstance.endTime_inuserformat)}
        <div class="proc-var camunda-end-time form-group">
            <label>{sugar_translate label='LBL_END_TIME' module='CamundaProcesses'}:</label>
            <div>{$processInstance.endTime_inuserformat}</div>
        </div>
        {/if}
        {foreach from=$processInstance.variables item="variable"}
        {if !empty($variable.value_inuserformat)}
        <div class="proc-var form-group">
            <label>{$variable.label}:</label>
            <div>{$variable.value_inuserformat}</div>
        </div>
        {/if}
        {/foreach}
        </div>
    </div>

    {if !empty($processInstance.tasks)}
    <div class="camunda-tasks">
        {foreach from=$processInstance.tasks item="task"}
        <div class="camunda-task detail-view-field" data-id="{$task.id}" data-key="{$task.taskDefinitionKey}" role="tabform">
            <h4>{sugar_translate label='LBL_TASK' module='CamundaProcesses'}: {$task.name}</h4>

            <div class="assignee">
                <label>{sugar_translate label='LBL_ASSIGNEE' module='CamundaProcesses'}:</label>
                {if !empty($task.assignee)}
                    <span class="task__assignee">
                    {if $task.assignee_id}
                        <a href="index.php?module=Employees&action=DetailView&record={$task.assignee_id}">{$task.assignee_full_name}</a>
                    {else}
                        {$task.assignee}
                    {/if}
                    </span>
                {else}
                    &ndash;
                {/if}
            </div>

            <ul class="subpanelTablist tab-list" role="tablist">
                <li role="presentation"><a class="current" aria-controls="complete" role="tab" data-toggle="tab1" onclick="tabNavigate(this)">
                    {sugar_translate label='LBL_TASK_FORM' module='CamundaProcesses'}</a></li>
                <li role="presentation"><a aria-controls="assign" role="tab" data-toggle="tab2" onclick="tabNavigate(this)">
                    {sugar_translate label='LBL_ASSIGN' module='CamundaProcesses'}</a></li>
                <li role="presentation"><a aria-controls="history" role="tab" data-toggle="tab3" onclick="tabNavigate(this)">
                    {sugar_translate label='LBL_HISTORY' module='CamundaProcesses'}</a></li>
                <li role="presentation"><a aria-controls="diagram" role="tab" data-toggle="tab4"
                    onclick="loadBpmnDiagram('{$task.id|escape}', '{$task.taskDefinitionKey|escape}'
                        , '{$task.processDefinitionId|escape}'
                        , '{$bean->module_name|escape}', '{$bean->id|escape}'); tabNavigate(this);">
                    {sugar_translate label='LBL_DIAGRAM' module='CamundaProcesses'}</a></li>
                {if !empty($task.description)}
                <li role="presentation"><a aria-controls="description" role="tab" data-toggle="tab5" onclick="tabNavigate(this)">
                    {sugar_translate label='LBL_DESCRIPTION' module='CamundaProcesses'}</a></li>
                {/if}
            </ul>

            <div class="tab-content" role="tabcontent">
                <div role="tabpanel" class="camunda-task-form tab-panel active" data-tabpanel="tab1">
                    <div class="detail-view-field">
                        {$task.form.html}
                    </div>
                    <script>
                    var taskId = {$task.id|@json_encode};
                    var variables = {$task.form.variables|@json_encode};
                    addForm(taskId);
                    var form = $('.camunda-task[data-id="'+taskId+'"] .camunda-task-form form')
                        .attr('method', 'POST').attr('action', 'index.php').attr('name', taskId).attr('id', taskId)
                        .addClass('camunda-sugar-task-form').addClass('camunda-task-form').wrapInner('<div class="detail-view-field">').append(
                            '<div class="form-group submit-group">\
                                <input type="hidden" name="module" value="CamundaProcesses" />\
                                <input type="hidden" name="action" value="SaveTask" />\
                                <input type="hidden" name="sugar_module" value="{$bean->module_name}" />\
                                <input type="hidden" name="sugar_record" value="{$bean->id}" />\
                                <input type="hidden" name="camunda_field_name" value="{$camunda_field_name}" />\
                                <input type="hidden" name="task_id" value="{$task.id}" />\
                                <div class="overall_validation"><input type="hidden" name="overall_validation" value="1" /></div>\
                                <input type="submit" name="complete" class="button primary"\
                                    value="{sugar_translate label='LBL_COMPLETE_TASK' module='CamundaProcesses'}"\
                                    {if empty($task.canSave)} disabled="disabled"{/if} \
                                    onclick="var _form = document.getElementById(\'' + taskId+ '\');\
                                        if(check_form(\'' + taskId + '\'))\
                                            SUGAR.ajaxUI.submitForm(_form);\
                                        return false;"\
                                />\
                            </div>'
                        )
                    {literal}
                    for (var i in variables) {
                        if (variables[i].type === 'Boolean') {
                            form.find('[name="'+i+'"]').prop('checked', !!variables[i].value);
                        }
                        else {
                            form.find('[name="'+i+'"]').val(variables[i].value);
                        }
                    }
                    {/literal}
                    form.attr('data-sugared', 'true')
                    </script>
                </div>
                <div role="tabpanel" class="tab-panel" id="{$task.id}_assign" data-tabpanel="tab2">
                    <div>
                        {foreach from=$task.identity.assignees item="assignee"}
                        <div class="form-group">
                            <label>{sugar_translate label='LBL_ASSIGNEE' module='CamundaProcesses'}:</label> {$assignee}
                        </div>
                        {/foreach}
                        <form method="POST" action="index.php" id="{$task.id}_assign_form" name="{$task.id}_assign_form"
                            class="camunda-sugar-assign-form camunda-assign-form">
                            <div class="form-group">
                                <label>{sugar_translate label='LBL_NEW_ASSIGNEE' module='CamundaProcesses'}:</label>
                                {html_options name=assigned_user_id options=$task.identity.candidate_users
                                    class="form-control" selected=$task.assignee_id}
                            </div>
                            <div class="form-group submit-group">
                                <input type="hidden" name="module" value="CamundaProcesses" />
                                <input type="hidden" name="action" value="Assign" />
                                <input type="hidden" name="sugar_module" value="{$bean->module_name}" />
                                <input type="hidden" name="sugar_record" value="{$bean->id}" />
                                <input type="hidden" name="camunda_field_name" value="{$camunda_field_name}" />
                                <input type="hidden" name="task_id" value="{$task.id}" />
                                <input type="submit" class="button primary"
                                    value="{sugar_translate label='LBL_ASSIGN' module='CamundaProcesses'}"
                                    {if empty($task.canAssign)} disabled="disabled"{/if}
                                    onclick="var _form = document.getElementById('{$task.id}_assign_form');
                                        SUGAR.ajaxUI.submitForm(_form);
                                        return false;"
                                />
                            </div>
                        </form>
                    </div>
                </div>
                <div role="tabpanel" class="tab-panel" id="{$task.id}_history" data-tabpanel="tab3">
                    {* TODO: form to add task comment *}
                    <div class="camunda-task__history detail-view-field">
                        <div class="form-group">
                            <label>{if !empty($task.created_inuserformat)}{$task.created_inuserformat}{else}{$task.created}{/if}</label>
                            <span class="task__created">
                                {sugar_translate label='LBL_TASK_CREATION' module='CamundaProcesses'}
                            </span>
                        </div>
                        {foreach from=$task.history item="userOperation"}
                        <div class="form-group">
                            <label>{$userOperation.timestamp_inuserformat}</label>
                            {$userOperation.operation_inuserformat} '{$userOperation.orgValue_inuserformat|default:$userOperation.orgValue}'
                            <span class="arr">&rarr;</span>
                            '{$userOperation.newValue_inuserformat|default:$userOperation.newValue}'
                            {if !empty($userOperation.userId_inuserformat)} ({$userOperation.userId_inuserformat}){/if}
                        </div>
                        {* TODO: in user format *}
                        {/foreach}
                    </div>
                </div>
                <div role="tabpanel" class="tab-panel" id="{$task.id}_diagram" data-tabpanel="tab4">
                    {if $current_user_is_admin}
                    <div class="admin-only">
                        <a href="index.php?module=CamundaProcesses&action=Bpmn&sugar_module={$bean->module_name}&sugar_record={$bean->id}&definition_id={$task.processDefinitionId}"
                            target="_blank" rel="noopener noreferrer">
                            XML
                        </a>
                    </div>
                    {/if}
                    <div class="canvas" id="bpmn_canvas_{$task.id}" style="height: 500px"></div>
                </div>
                {if !empty($task.description)}
                <div role="tabpanel" class="tab-panel" id="{$task.id}_description" data-tabpanel="tab5">
                    <div class="task-description">
                    {$task.description}
                    </div>
                </div>
                {/if}
            </div>
        </div>
        {/foreach}
    </div>
    {/if}

    {if !empty($processInstance.history)}
    <div class="camunda-history detail-view-field">
        <h4>{sugar_translate label='LBL_HISTORY' module='CamundaProcesses'}</h4>
        {foreach from=$processInstance.history item="activity"}
        <div class="camunda-activity form-group">
            <label>
            <span class="startTime">{$activity.startTime_inuserformat}</span>
            {if $activity.activityType == "userTask" || $activity.activityType == "serviceTask"}
            &ndash;
                {if !empty($activity.endTime)}
                {$activity.endTime_inuserformat}
                {else}
                &hellip;
                {/if}
            {/if}
            </label>
            <div>
            <span class="activityName">{$activity.activityName}</span>
            {if !empty($activity.owner_id)}
                (<a href="index.php?module=Employees&action=DetailView&record={$activity.owner_id}">{$activity.owner_full_name}</a>)
            {elseif !empty($activity.assignee_id)}
                (<a href="index.php?module=Employees&action=DetailView&record={$activity.assignee_id}">{$activity.assignee_full_name}</a>)
            {/if}
            {if !empty($activity.canceled)}
                ({sugar_translate label='LBL_CANCELED' module='CamundaProcesses'})
            {/if}
            {if !empty($activity.externalTasks)}
                {foreach from=$activity.externalTasks item="externalTask"}
                    {if !empty($externalTask.errorMessage)}
                    <div class="error">
                        {$externalTask.errorMessage}
                        {if $current_user_is_admin}
                        <br />{$externalTask.errorDetails}
                        {/if}
                    </div>
                    {/if}
                {/foreach}
            {/if}
            </div>
        </div>
        {/foreach}
    </div>
    {/if}

</div>
{/foreach}
</div>

{* TODO: angular validate form fields *}
{* TODO: submit forms with ajax to stay in same tab *}
<script>
{literal}
function loadBpmnDiagram(taskId, taskDefinitionKey, processDefinitionId, module, record) {
    var canvasElem = $('#bpmn_canvas_'+taskId)
    if (canvasElem.children().length) {
        return;
    }
    canvasElem.html('<span>' + SUGAR.language.get('app_strings', 'LBL_LOADING') + '</span>')
    $.ajax({
        url: "modules/CamundaProcesses/bpmn-js/bpmn-navigated-viewer.production.min.js",
        dataType: "script",
        cache: true,
    })
    .done(function() {
        $.get('index.php?module=CamundaProcesses&action=Bpmn', {
              sugar_module: module
            , sugar_record: record
            , definition_id: processDefinitionId
        }, null, 'text')
        .done(function (xml) {
            canvasElem.html('')
            var viewer = new BpmnJS({ container: '#' + canvasElem.attr('id') });
            viewer.importXML(xml, function(err) {
                if (err) {
                    console.error(err);
                    canvasElem.html(
                        $('<span class="error">').text(
                            'Bpmn importXML error'
                        ))
                } else {
                    var canvas = viewer.get('canvas');
                    canvas.zoom('fit-viewport');
                    var element = canvas.getGraphics(taskDefinitionKey);
                    canvas.addMarker(element, "highlight");
                }
            });
        })
        .fail(function(xhr) {
            canvasElem.html(
                $('<span class="error">').text(
                    xhr.responseText || "Can't load diagram"
                ))
        })
    })
}
function tabNavigate(elem)
{
    var targetTab = $(elem).data("toggle");
    var tabform = $(elem).closest('[role="tabform"]');

    $(tabform).find('[role="tablist"]').find('[role="tab"]').removeClass("current");
    $(tabform).find('[role="tabpanel"]').removeClass("active");

    $(tabform).find('[role="tablist"]').find('[role="tab"][data-toggle="'+targetTab+'"]').addClass("current");
    $(tabform).find('[role="tabpanel"][data-tabpanel="'+targetTab+'"]').addClass("active");
}
</script>
{/literal}