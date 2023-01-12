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

    <div class="camunda-variables detail-view-field">
        <h4>{sugar_translate label='LBL_VARIABLES' module='CamundaProcesses'}</h4>
        {foreach from=$processInstance.variables item="variable"}
        {if !empty($variable.value_inuserformat)}
        <div class="row camunda-variable">
            <div class="col-md-6"><label>{$variable.label}:</label></div>
            <div class="col-md-6">{$variable.value_inuserformat}</div>
        </div>
        {/if}
        {/foreach}
        {if !empty($processInstance.startTime_inuserformat)}
        <div class="row camunda-start-time">
            <div class="col-md-6"><label>{sugar_translate label='LBL_START_TIME' module='CamundaProcesses'}:</label></div>
            <div class="col-md-6">{$processInstance.startTime_inuserformat}</div>
        </div>
        {/if}
        {if !empty($processInstance.endTime_inuserformat)}
        <div class="row camunda-end-time">
            <div class="col-md-6"><label>{sugar_translate label='LBL_END_TIME' module='CamundaProcesses'}:</label></div>
            <div class="col-md-6">{$processInstance.endTime_inuserformat}</div>
        </div>
        {/if}
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

            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#{$task.id}_complete" aria-controls="complete" role="tab" data-toggle="tab">
                    {sugar_translate label='LBL_TASK_FORM' module='CamundaProcesses'}</a></li>
                <li role="presentation"><a href="#{$task.id}_assign" aria-controls="assign" role="tab" data-toggle="tab">
                    {sugar_translate label='LBL_ASSIGN' module='CamundaProcesses'}</a></li>
                <li role="presentation"><a href="#{$task.id}_history" aria-controls="history" role="tab" data-toggle="tab">
                    {sugar_translate label='LBL_HISTORY' module='CamundaProcesses'}</a></li>
                <li role="presentation"><a href="#{$task.id}_diagram" aria-controls="diagram" role="tab" data-toggle="tab"
                    onclick="loadBpmnDiagram('{$task.id|escape}', '{$task.taskDefinitionKey|escape}'
                        , '{$task.processDefinitionId|escape}'
                        , '{$bean->module_name|escape}', '{$bean->id|escape}');">
                    {sugar_translate label='LBL_DIAGRAM' module='CamundaProcesses'}</a></li>
                {if !empty($task.description)}
                <li role="presentation"><a href="#{$task.id}_description" aria-controls="description" role="tab" data-toggle="tab">
                    {sugar_translate label='LBL_DESCRIPTION' module='CamundaProcesses'}</a></li>
                {/if}
            </ul>

            <div class="tab-content" role="tabcontent">
                <div role="tabpanel" class="tab-pane active" id="{$task.id}_complete">
                    <div class="row camunda-task-form">
                        {$task.form.html}
                    </div>
<script>
console.log ('form',{$task.form.html|@json_encode});
</script>
                    <div class="row">
                      <form method="POST" class="camunda-suite-task-form" action="index.php" name={$task.id} id={$task.id}>
                          <div>
                                <input type="hidden" name="module" value="CamundaProcesses" />
                                <input type="hidden" name="action" value="SaveTask" />
                                <input type="hidden" name="sugar_module" value="{$bean->module_name}" />
                                <input type="hidden" name="sugar_record" value="{$bean->id}" />
                                <input type="hidden" name="camunda_field_name" value="{$camunda_field_name}" />
                                <input type="hidden" name="task_id" value="{$task.id}" />
                                <input type="submit" name="complete" class="button primary"
                                    value="{sugar_translate label='LBL_COMPLETE_TASK' module='CamundaProcesses'}"
                                    {if empty($task.canSave)} disabled="disabled"{/if} 
                                    onclick="var _form = document.getElementById('{$task.id}');
                                        if(check_form('{$task.id}'))
                                            SUGAR.ajaxUI.submitForm(_form);
                                        return false;"
                                />
                            </div>
                      </form>
                    </div>
                    <script>
                    var variables = {$task.form.variables|@json_encode};
                    var taskId = {$task.id|@json_encode};
                    var form = $('.camunda-task[data-id="'+taskId+'"] .camunda-task-form form')
                    {literal}
                    var form1 = $('.camunda-task[data-id="'+taskId+'"] .camunda-suite-task-form');
                    form.children().prependTo(form1);
                    for (var i in variables) {
                        if (variables[i].type === 'Boolean') {
                            form.find('[name="'+i+'"]').prop('checked', !!variables[i].value);
                        }
                        else {
                            form.find('[name="'+i+'"]').val(variables[i].value);
                        }
                    }
                    {/literal}
                    </script>
                </div>
                <div role="tabpanel" class="tab-pane" id="{$task.id}_assign">
                    <div class="camunda-task-identity">
                        {foreach from=$task.identity.assignees item="assignee"}
                        <div>
                            <label>{sugar_translate label='LBL_ASSIGNEE' module='CamundaProcesses'}:</label> {$assignee}
                        </div>
                        {/foreach}
                        <form method="POST" action="index.php" id="{$task.id}_assign_form" name="{$task.id}_assign_form">
                            <div class="form-group">
                                <label>{sugar_translate label='LBL_NEW_ASSIGNEE' module='CamundaProcesses'}:</label>
<!--
                                {html_options name=assigned_user_id options=$task.identity.all_group_users
                                    class="form-control" selected=$task.assignee_id}
-->
                                {html_options name=assigned_user_id options=$task.identity.candidate_users
                                    class="form-control" selected=$task.assignee_id}
                            </div>
                            <div>
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

                <div role="tabpanel" class="tab-panel" id="{$task.id}_history">
                    {* TODO: form to add task comment *}
                    <div class="row camunda-task__history detail-view-field">
                        {foreach from=$task.history item="userOperation"}
                        <div>
                            <label>{$userOperation.timestamp_inuserformat}</label>
                            {$userOperation.operation_inuserformat} '{$userOperation.orgValue_inuserformat|default:$userOperation.orgValue}'
                            <span class="arr">&rarr;</span>
                            '{$userOperation.newValue_inuserformat|default:$userOperation.newValue}'
                            {if !empty($userOperation.userId_inuserformat)} ({$userOperation.userId_inuserformat}){/if}
                        </div>
                        {* TODO: in user format *}
                        {/foreach}
                            <!-- <label>{if !empty($task.created_inuserformat)}{$task.created_inuserformat}{else}{$task.created}{/if}</label> -->
                            <label>{sugar_translate label='LBL_CREATED' module='CamundaProcesses'}:</label>
                            <span class="task__created">
<!--                                {sugar_translate label='LBL_TASK_CREATION' module='CamundaProcesses'}  -->
                                {if !empty($task.created_inuserformat)}{$task.created_inuserformat}{else}{$task.created}{/if}
                            </span>
                    </div>
                </div>

                <div role="tabpanel" class="tab-panel" id="{$task.id}_diagram">
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
                <div role="tabpanel" class="tab-pane" id="{$task.id}_description"">
                    <div class="detail-view-field">
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
        <div class="camunda-activity">
            <span class="startTime">{$activity.startTime_inuserformat}</span>
            {if $activity.activityType == "userTask" || $activity.activityType == "serviceTask"}
            &ndash;
                {if !empty($activity.endTime)}
                {$activity.endTime_inuserformat}
                {else}
                &hellip;
                {/if}
            {/if}
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
</script>
{/literal}
