<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 */

require_once 'modules/CamundaProcesses/SugarCamunda.php';
require_once 'modules/CamundaProcesses/CamundaProcessDefinition.php';

class CamundaForm
{
    public static function definitionFormSelector($defId, $panelName)
    {
        return ".camunda-panel[data-field=\"{$panelName}\"] .camunda-process-definition[data-id=\"{$defId}\"] form";
    }

    public static function taskFormSelector($taskId, $panelName)
    {
        return ".camunda-panel[data-field=\"{$panelName}\"] .camunda-task[data-id=\"{$taskId}\"] .camunda-task-form form";
    }

    public static function parseFormVariables($html)
    {
        $doc = new DOMDocument();
        $doc->strictErrorChecking = false;
        $doc->loadHTML($html);
        $xml = simplexml_import_dom($doc);
        if (!empty($xml)) {
            $variables = array();
            $inputs = $xml->xpath('.//*[@cam-variable-type and @cam-variable-name and @name]');
            foreach ($inputs as $input) {
                $variables[(string) $input['cam-variable-name']] = array(
                    'value' => '',
                    'type' => (string) $input['cam-variable-type'],
                );
            }
            return $variables;
        }
        return false;
    }

    public static function prependEmptyOption($formSelector, $selectSelector = 'select')
    {
        $formSelectorJs = json_encode($formSelector);
        $selectSelector = json_encode($selectSelector);
        return <<<HTML
<script>
\$($formSelectorJs).find($selectSelector).prepend('<option value=""></option>').val('')
</script>
HTML;
    }

    public static function disableReadonlyFields($formSelector)
    {
        $formSelectorJs = json_encode($formSelector);
        return <<<HTML
<script>
\$($formSelectorJs).find('[readonly]').prop('disabled', true)
</script>
HTML;
    }

    public static function makeTextarea($formSelector, $inputSelector)
    {
        $formSelectorJs = json_encode($formSelector);
        $inputSelectorJs = json_encode($inputSelector);
        return <<<HTML
<script>
\$($formSelectorJs).find($inputSelectorJs).each(function() {
    var input = \$(this)
    input.replaceWith(\$('<textarea>')
        .attr('name', input.attr('name') || '')
        .attr('id', input.attr('id') || '')
        .prop('readonly', input.prop('readonly') || false)
        .prop('disabled', input.prop('disabled') || false)
        .text(input.val() || '')
    )
})
</script>
HTML;
    }

    public static function initRelateField($formSelector, $varName, $module, $idValue, $nameValue, $params = array())
    {
        $formSelectorJs = json_encode($formSelector);
        $idValueJs = json_encode($idValue);
        $nameValueJs = json_encode($nameValue);
        $initialFilter = !empty($params['initial_filter']) ? $params['initial_filter'] : '';
        return <<<HTML
<script>
SUGAR.util.doWhen(function() {return \$($formSelectorJs).attr("data-sugared") == "true"}, function() {
var form = \$($formSelectorJs)
var input = form.find('[name="{$varName}"]').attr('name', '{$varName}_name').attr('id', '{$varName}_name').val($nameValueJs)
    .prop('readonly', true)
input.after(' '
    , $('<input type="hidden">').attr('name', "{$varName}").attr('id', "{$varName}").val({$idValueJs})
        .prop('readonly', !!input.prop('readonly')).prop('disabled', !!input.prop('disabled'))
    , $('<span class="id-ff multiple">').append(
        $('<button type="button" class="button firstChild" value="Select">')
            .attr('name', "btn_{$varName}_name").attr('id', "btn_{$varName}_name")
            .prop('readonly', !!input.prop('readonly')).prop('disabled', !!input.prop('disabled'))
            .attr('onclick', 'open_popup("{$module}", 600, 400, ' + "\"{$initialFilter}\""
            + ', true, false, {"call_back_function":"set_return","form_name":"'+form.attr('name')
            +'","field_to_name_array":{"id":"{$varName}","name":"{$varName}_name"}}, "single", true);').append(
                '<img src="themes/default/images/id-ff-select.png">'
            )
        , $('<button type="button" class="button lastChild" value="Clear">')
            .attr('name', "btn_clr_{$varName}_name").attr('id', "btn_clr_{$varName}_name")
            .prop('readonly', !!input.prop('readonly')).prop('disabled', !!input.prop('disabled'))
            .attr('onclick', "SUGAR.clearRelateField(this.form, '{$varName}_name', '{$varName}');").append(
                '<img src="themes/default/images/id-ff-clear.png">'
            )
    )
)
});
</script>
HTML;
    }

    public static function getUserId($val, $field)
    {
        if ($field === 'id') {
            return $val;
        }
        $user = $GLOBALS['db']->fetchOne("SELECT id FROM users WHERE $field = "
            .$GLOBALS['db']->quoted($val)." AND deleted = 0");
        if (!empty($user)) {
            return $user['id'];
        }
        return '';
    }

    public static function getUserFullName($val, $field)
    {
        $user = $GLOBALS['db']->fetchOne("SELECT id, first_name, last_name FROM users WHERE $field = "
            .$GLOBALS['db']->quoted($val)." AND deleted = 0");
        if (!empty($user)) {
            return $GLOBALS['locale']->getLocaleFormattedName($user['first_name'], $user['last_name']);
        }
        return '';
    }

    public static function getUsersAsOptions($users)
    {
        $usersOptions = array('' => '');
        foreach ($users as $user) {
            // $full_name = $GLOBALS['locale']->getLocaleFormattedName($user->first_name, $user->last_name);
            $full_name = $user->last_name . ' ' . $user->first_name; //according to sorting
            $usersOptions[$user->id] = $full_name;
        }
        return $usersOptions;
    }

    public static function dateTimeToUserFormat($datetime)
    {
        $time = strtotime($datetime);
        return $GLOBALS['timedate']->to_display_date_time(
            gmdate('Y-m-d H:i:s', $time), true, true, $GLOBALS['current_user']);
    }

    public static function formatTaskHistory(&$history, $params = array())
    {
        foreach ($history as &$op) {
            $op['timestamp_inuserformat'] = CamundaForm::dateTimeToUserFormat($op['timestamp']);

            if ($op['operationType'] === 'Assign' && $op['property'] === 'assignee') {
                $op['operation_inuserformat'] = translate('LBL_USER_WAS_ASSIGNED', 'CamundaProcesses');
            }
            else {
                $op['operation_inuserformat'] = $op['operationType'] . ' ' . $op['property'];
            }

            if (!empty($GLOBALS['sugar_config']['camunda']['camunda_user']['login'])
                && $op['userId'] === $GLOBALS['sugar_config']['camunda']['camunda_user']['login'])
            { //author is default rest user
                $op['userId_inuserformat'] = '';
            }
            else {
                $op['userId_inuserformat'] = $op['userId'];
            }

            if ($op['property'] === 'assignee' && !empty($params['userIdField'])) {
                if (!empty($op['orgValue'])) {
                    $fullName = self::getUserFullName($op['orgValue'], $params['userIdField']);
                    if ($fullName) {
                        $op['orgValue_inuserformat'] = '<a href="index.php?module=Employees&action=DetailView&record='
                            . self::getUserId($op['orgValue'], $params['userIdField']) . '">'
                            . $fullName . '</a>';
                    }
                }
                if (!empty($op['newValue'])) {
                    $fullName = self::getUserFullName($op['newValue'], $params['userIdField']);
                    if ($fullName) {
                        $op['newValue_inuserformat'] = '<a href="index.php?module=Employees&action=DetailView&record='
                            . self::getUserId($op['newValue'], $params['userIdField']) . '">'
                            . $fullName . '</a>';
                    }
                }
            }

            $op['timestamp_inuserformat'] = CamundaForm::dateTimeToUserFormat($op['timestamp']);
        }
        unset($op);
    }

    public static function getFormFieldLabel($formField, $processDefinitionId, $selectedValue = null)
    {
        static $bpmnStrings;
        static $bpmnListStrings;
        if (!isset($bpmnStrings[$processDefinitionId])) {
            //TODO: store to cache dir too
            $bpmnStrings[$processDefinitionId] = array();
            $bpmnListStrings[$processDefinitionId] = array();
            $text = CamundaProcessDefinition::getBpmnXml($processDefinitionId);
            $xml = simplexml_load_string($text);
            if (!empty($xml)) {
                $fields = $xml->xpath('.//camunda:formField[@id and @label]');
                foreach ($fields as $field) {
                    $bpmnStrings[$processDefinitionId][(string)$field['id']] = (string)$field['label'];
                    if ($field['type'] == 'enum') {
                        $values = $field->xpath('./camunda:value[@id and @name]');
                        foreach ($values as $value) {
                            $bpmnListStrings[$processDefinitionId] [(string)$field['id']]
                                [(string)$value['id']] = (string)$value['name'];
                        }
                    }
                }
            }
        }
        if ($selectedValue !== null) {
            return isset($bpmnListStrings[$processDefinitionId][$formField][$selectedValue])
                ? $bpmnListStrings[$processDefinitionId][$formField][$selectedValue]
                : (is_string($selectedValue) ? nl2br($selectedValue) : $selectedValue);
        }
        return !empty($bpmnStrings[$processDefinitionId][$formField])
            ? $bpmnStrings[$processDefinitionId][$formField]
            : $formField;
    }

    public static function fillVariables($unsafeSource, &$variables)
    {
        foreach ($variables as $name => &$var) {
            $var['value'] = $unsafeSource[$name];
            if ($var['type'] === 'String') {
                if (empty($var['value'])) {
                    $var['value'] = null; //for enum
                }
            }
            elseif ($var['type'] === 'Boolean') {
                $var['value'] = isset($unsafeSource[$name]);
            }
        }
        //TODO: unset readonly variables
        unset($var);
    }

    public static function translateVariables(&$variables, $processDefinitionId)
    {
        foreach ($variables as $key => &$variable) {
            $varName = !empty($variable['name']) ? $variable['name'] : $key;
            if ($variable['type'] == 'Json') {
                unset($variables[$key]);
            }
            $variable['field'] = $varName;
            $variable['label'] = CamundaForm::getFormFieldLabel($varName, $processDefinitionId);
            $variable['value_inuserformat'] = $variable['value'];
            if (!empty($variable['value'])) {
                if ($variable['type'] == 'Date') {
                    $variable['value_inuserformat'] = CamundaForm::dateTimeToUserFormat($variable['value']);
                }
                elseif ($variable['type'] == 'Boolean') {
                    $variable['value_inuserformat'] = $variable['value'] === true ? translate('checkbox_dom', '', '1')
                        : ($variable['value'] === false ? translate('checkbox_dom', '', '2') : '');
                }
                else {
                    $variable['value_inuserformat'] = CamundaForm::getFormFieldLabel($varName, $processDefinitionId, $variable['value']);
                }
            }
        }
        unset($variable);
    }
}
