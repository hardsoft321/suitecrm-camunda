# Integration of `SuiteCRM` with `Camunda`

## Installing `SuiteCRM` integration package with `Camunda`

1. Make a zip archive of the package and install the package through the modules loader;
2. In the root folder `SuiteCRM` execute the command

   ```sh
   composer install
   ```
   The `php` package `educoder/pest` should be installed;

3. Specify access to the `Camunda` server in the `SuiteCRM` configuration file.

   Example:

   ```php
   'camunda' => [
       'engine_url' => 'http://localhost:8080/engine-rest',
       'url' => 'http://localhost:8080',
   ]
   ```
   

## Adding a business process to the `SuiteCRM` module

1. Create a `Camunda` business process diagram and install it on the `Camunda` server.
   
2. Add key fields to the `SuiteCRM` module to display the approval panel and the processing history block. Below is an example of fields for a business process in the `AOS_Quotes` module:

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

   The `processes` array contains the list of business processes connected to the module (several business processes can be connected to the module).
    
   The array keys are business processes IDs in the `bpmn` schema in `Camunda` (`<bpmn:process id="quoteApproval"...`).

   The values ​​of the `processes` array define the business process handler classes in `SuiteCRM`:

   * `include` - path to the file where the class is defined;
   * `class` - class name.

   The class `CamundaProcess` is specified by default (supplied in the integration package `SuiteCRM` with `Camunda`):

   ```php
   array(
         'include' => 'modules/CamundaProcesses/CamundaProcess.php',
         'class' => 'CamundaProcess',
     )
   ```

   If a non-standard procedure for processing is required, then it is necessary:
   - create your own class, inheriting it from the `CamundaProcess` class;
   - write the name of this class and the path to the file containing the definition of this class in the `processes` array.
   - 
3. Add a translation of the name of the approval panel to the module. For example,

   ```php
   $mod_strings = array (
    'LBL_PANEL_BUSINESS_PROCESS' => 'Business Process',
    'LBL_BUSINESS_PROCESS' => 'Process',
   );
   ```

4. Add an approval panel to `detailviewdefs`.

   * Add to `tabDefs` array:

   ```php
     'LBL_PANEL_BUSINESS_PROCESS' => array (
          'newTab' => true,
          'panelDefault' => 'expanded',
        ),
   ```

   * Add to the `panels` array (the `name` value is the name of the added key field):

     ```php
     'LBL_PANEL_BUSINESS_PROCESS' => array (
        array(
          array(
            'name' => 'camunda_process',
            'hideLabel' => true,
          ),
        ),
      ),

     ```
Add changes to `SuiteCRM` manually (after that, perform the "restore" procedure), or by issuing a package and installing it using the module loader.

В папке example находится пример настройки интеграции процесса для модуля `AOS_Quotes`. 
Смортрие инструкция по установке примера в файле `example/README-ru.md`.

The folder `example` contains an example of process integration settings for the `AOS_Quotes` module. 
See the instructions for installing the example in the file `example/README.md`.
