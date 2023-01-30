# Интеграция `SuiteCRM` с `Camunda`

## Установка пакета интеграции `SuiteCRM` с `Camunda`

1. Сделать zip-архив пакета и установить пакет через загрузчик модулей;
2. В корневой папке `SuiteCRM` выполнить команду 

   ```sh
   composer install 
   ```
   Должен установится `php` пакет `educoder/pest`;

3. В конфигурационном файле `SuiteCRM` прописать доступ к серверу `Camunda`. 

   Пример: 

   ```php
   'camunda' => [
       'engine_url' => 'http://localhost:8080/engine-rest',
       'url' => 'http://localhost:8080',
   ]
   ```
   

## Добавление бизнес-процесса в модуль `SuiteCRM`

1. Создать схему бизнес-процесса `Camunda` и установить ее на сервер `Camunda`.
   
2. В модуль `SuiteCRM` добавить поля-ключи для отображения панели согласований и блока истории обработки бизнес-процесса. Ниже приведен пример полей для бизнес-процесса в модуле `AOS_Quotes`:

   ```php
   $dictionary['AOS_Quotes']['fields']['camunda_process'] = array (
    'name' => 'camunda_process',
    'type' => 'CamundaProcess',
    'source'=>'non-db',
    'inline_edit' => false,
    'vname'=>'LBL_BUSINESS_PROCESS',
    'processes' => array(
        'quoteApproval' => array(
            'include' => 'modules/CamundaProcesses/CamundaProcess.php',
            'class' => 'CamundaProcess',
        ),
     ),
    );

    $dictionary['AOS_Quotes']['fields']['camunda_history'] = array(
        'name' => 'camunda_history',
        'type' => 'CamundaProcess',
        'source' => 'non-db',
        'inline_edit' => false,
        'vname' => 'LBL_BUSINESS_PROCESS',
        'show_process_instances' => false,
        'show_historic_processes' => true,
        'processes' => $dictionary['AOS_Quotes']['fields']['camunda_process']['processes'],
    );
    ```

   Массив `processes` содержит перечень подключенных к модулю бизнес-процессов (к модулю может быть подключено несколько бизнес-процессов). 
    
   Ключи массива  - идентификаторы бизнес-процессов в схеме `bpmn` в `Camunda` (`<bpmn:process id="quoteApproval"...`). 

   Значения массива `processes` определяют классы-обработчики бизнес-процессов в `SuiteCRM`:

   * `include` - путь к файлу, в котором определен класс;
   * `class` - название класса.

   По умолчанию указывается класс `CamundaProcess` (поставляется в пакете интеграции `SuiteCRM` с `Camunda`):

   ```php
   array(
         'include' => 'modules/CamundaProcesses/CamundaProcess.php',
         'class' => 'CamundaProcess',
     )
   ```

   Если требуется нестандартная процедура обработки бизнес-процесса, то необходимо:
   - создать свой класс, унаследовав его от класса `CamundaProcess`;
   - прописать название этого класса и путь до файла, содержащего определение этого класса в массиве `processes`.

3. В модуль добавить перевод названия панели согласования. Например,

   ```php
   $mod_strings = array (
    'LBL_PANEL_BUSINESS_PROCESS' => 'Бизнес-процесс',
    'LBL_BUSINESS_PROCESS' => 'Процесс',
   );
   ```

4. В `detailviewdefs` добавить панель согласования. 

   * В массив `tabDefs` добавить:

   ```php
     'LBL_PANEL_BUSINESS_PROCESS' => array (
          'newTab' => true,
          'panelDefault' => 'expanded',
        ), 
   ```

   * В массив `panels` добавить (значение `name` - название добавленного поля-ключа):

     ```php
     'LBL_PANEL_BUSINESS_PROCESS' => array (        
        array (
          array(
            'name' => 'camunda_process',
            'hideLabel' => true,
          ),
        ),
      ),

     ```

Добавить изменения в `SuiteCRM` вручную (после чего, выполнить процедуру "восстановления"), или оформив пакет, и установив его при помощи загрузчика модулей.
