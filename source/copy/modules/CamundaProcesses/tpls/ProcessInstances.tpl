{literal}
<style>
[ng-if] {
    display: none;
}
.camunda-panel .form-group {
    clear: both;
}
label {
    margin-bottom: 0;
}
.camunda-task > .attr > div > label {
    padding-left: 20px;
}
.camunda-variables, .camunda-task {
    margin-bottom: 15px;
}
.canvas .highlight .djs-outline {
    stroke-width: 2px !important;
    stroke: #155cb5 !important;
    fill: rgba(194,213,237,0.4) !important;
}
</style>
{/literal}

<div class="camunda-process-instances">
{foreach from=$processInstances item="processInstance"}
<div class="camunda-process-instance">
    <h3>{sugar_translate label='LBL_PROCESS' module='CamundaProcesses'}: {$processInstance.processDefinition.name}</h3>

    {if !empty($processInstance.variables)}
    <div class="camunda-variables detail-view-field">
        <h4>{sugar_translate label='LBL_VARIABLES' module='CamundaProcesses'}</h4>
        {foreach from=$processInstance.variables item="variable"}
        <div class="row camunda-variable">
            <div class="col-md-6"><label>{$variable.label}:</label></div>
            <div class="col-md-6">{$variable.value_inuserformat}</div>
        </div>
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
    {/if}

    {if !empty($processInstance.tasks)}
    <div class="camunda-tasks">
        {foreach from=$processInstance.tasks item="task"}
        <div class="camunda-task detail-view-field" data-id="{$task.id}">
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
                        , '{$bean->module_name|escape}', '{$bean->id|escape}')">
                    {sugar_translate label='LBL_DIAGRAM' module='CamundaProcesses'}</a></li>
                {if !empty($task.description)}
                <li role="presentation"><a href="#{$task.id}_description" aria-controls="description" role="tab" data-toggle="tab">
                    {sugar_translate label='LBL_DESCRIPTION' module='CamundaProcesses'}</a></li>
                {/if}
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="{$task.id}_complete">
                    <div class="row camunda-task-form">
                        {$task.form.html}
                    </div>
                    <div class="row">
                        <form method="POST" class="camunda-suite-task-form" action="index.php">
                            <div>
                                <input type="hidden" name="module" value="CamundaProcesses" />
                                <input type="hidden" name="action" value="SaveTask" />
                                <input type="hidden" name="sugar_module" value="{$bean->module_name}" />
                                <input type="hidden" name="sugar_record" value="{$bean->id}" />
                                <input type="hidden" name="task_id" value="{$task.id}" />
                                <input type="submit" name="complete" class="button primary"
                                   value="{sugar_translate label='LBL_COMPLETE_TASK' module='CamundaProcesses'}"
                                   {if empty($task.canSave)} disabled="disabled"{/if} />
                                {* <input type="submit" name="submit" class="button" value="{sugar_translate label='LBL_SAVE_TASK' module='CamundaProcesses'}" /> *}
                            </div>
                        </form>
                    </div>
                    <script>
                    var taskId = {$task.id|@json_encode};
                    var variables = {$task.form.variables|@json_encode};
                    {literal}
                    var form = $('.camunda-task[data-id="'+taskId+'"] .camunda-task-form form');
                    var form1 = $('.camunda-task[data-id="'+taskId+'"] .camunda-suite-task-form');
                    form.children().prependTo(form1);
                    for (var i in variables) {
                        form1.find('[name="'+i+'"]').val(variables[i].value);
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
                        <form method="POST" action="index.php">
                            <div class="form-group">
                                <label>{sugar_translate label='LBL_NEW_ASSIGNEE' module='CamundaProcesses'}</label>
                                {html_options name=assigned_user_id options=$task.identity.all_group_users
                                    class="form-control" selected=$task.assignee_id}
                            </div>
                            <div>
                                <input type="hidden" name="module" value="CamundaProcesses" />
                                <input type="hidden" name="action" value="Assign" />
                                <input type="hidden" name="sugar_module" value="{$bean->module_name}" />
                                <input type="hidden" name="sugar_record" value="{$bean->id}" />
                                <input type="hidden" name="task_id" value="{$task.id}" />
                                <input type="submit" class="button primary"
                                    value="{sugar_translate label='LBL_ASSIGN' module='CamundaProcesses'}"
                                    {if empty($task.canAssign)} disabled="disabled"{/if} />
                            </div>
                        </form>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="{$task.id}_history">
                    {* TODO: form to add task comment *}
                    <div class="row camunda-task__history detail-view-field">
                        {foreach from=$task.history item="userOperation"}
                        <div>{$userOperation.timestamp} {$userOperation.userId} {$userOperation.operationType} {$userOperation.property}: '{$userOperation.orgValue}' -> '{$userOperation.newValue}'</div>
                        {* TODO: in user format *}
                        {/foreach}
                        <label>{sugar_translate label='LBL_CREATED' module='CamundaProcesses'}:</label>
                        <span class="task__created">
                            {if !empty($task.created_inuserformat)}{$task.created_inuserformat}{else}{$task.created}{/if}
                        </span>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="{$task.id}_diagram">
                    <div class="canvas" id="bpmn_canvas_{$task.id}" style="height: 500px"></div>
                </div>
                {if !empty($task.description)}
                <div role="tabpanel" class="tab-pane" id="{$task.id}_description">
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
            {if $activity.activityType == "userTask"}
            &ndash;
                {if !empty($activity.endTime)}
                {$activity.endTime_inuserformat}
                {else}
                &hellip;
                {/if}
            {/if}
            : <span class="activityName">{$activity.activityName}</span>
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
function loadBpmnDiagram(taskId, taskDefinitionKey, module, record) {
    if (!$('#bpmnio').length) {
        $('body').append($('<script id="bpmnio" src="https://unpkg.com/bpmn-js@0.27.6/dist/bpmn-navigated-viewer.production.min.js">'))
    }
    var canvasElem = $('#bpmn_canvas_'+taskId)
    if (canvasElem.children().length) {
        return;
    }
    canvasElem.html('<span>' + SUGAR.language.get('app_strings', 'LBL_LOADING') + '</span>')

    SUGAR.util.doWhen("document.readyState == 'complete' && typeof BpmnJS != 'undefined'", function() {
        $.get('index.php?module=CamundaProcesses&action=Bpmn', {
              sugar_module: module
            , sugar_record: record
            , task_id: taskId
        })
        .done(function (xml) {
            canvasElem.html('')
            var viewer = new BpmnJS({ container: '#' + canvasElem.attr('id') });
            viewer.importXML(xml, function(err) {
                if (err) {
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
    });
}
</script>
{/literal}