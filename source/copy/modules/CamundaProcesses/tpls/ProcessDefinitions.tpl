{literal}
<style>
[ng-if] {
    display: none;
}
.camunda-panel .form-group {
    clear: both;
}
</style>
{/literal}

<div class="row camunda-process-definitions">
{foreach from=$processDefinitions item="processDefinition"}
    <div class="camunda-process-definition detail-view-field" data-id="{$processDefinition.id}">
        <div class="process-name"><label>{$processDefinition.name}</label></div>
        <div class="process-description">{$processDefinition.description}</div>
        <div class="camunda-process-definition-form-wrap">
            {$processDefinition.form.html}
        </div>
        <form method="POST" action="index.php" class="camunda-suite-form">
            <div class="form-group">
                <input type="hidden" name="module" value="CamundaProcesses" />
                <input type="hidden" name="action" value="Start" />
                <input type="hidden" name="sugar_module" value="{$bean->module_name}" />
                <input type="hidden" name="sugar_record" value="{$bean->id}" />
                <input type="hidden" name="definition_id" value="{$processDefinition.id}" />
                <input type="submit" class="button primary" value="{sugar_translate label='LBL_START_PROCESS' module='CamundaProcesses'}" />
            </div>
        </form>
<script>
var defId = {$processDefinition.id|@json_encode};
var form = $('.camunda-process-definition[data-id="'+defId+'"] .camunda-process-definition-form-wrap form');
var form1 = $('.camunda-process-definition[data-id="'+defId+'"] .camunda-suite-form');
form.children().prependTo(form1);
</script>
    </div>
{/foreach}
</div>
