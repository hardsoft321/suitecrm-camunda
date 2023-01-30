# An example of setting up a business process in `SuiteCRM`

The `example` folder contains an example of setting up the "Order Processing" business process for the `AOS_Quotes` module.

* `bpmn` - an example of a business process scheme for `Camunda`: bpmn scheme and necessary java classes (Russian and English versions);
* `camunda-example` - a package for adding settings in the `AOS_Quotes` module (without adding an approval panel to `detailviewdefs`);
* `camunda-example-ui` - a package for adding an agreement panel to the view form - `detailviewdefs`.

## Installing an example of setting up a business process in `SuiteCRM`

1. Install `SuiteCRM` integration package with `Camunda`;
2. write in the `SuiteCRM` configuration file access to the `Camunda` service;
3. install the `camunda-example` package;
4. Add an approval panel to the `detailviewdefs` of the `AOS_Quotes` module using one of the following methods:
   * if your installation of `SuiteCRM` does not require completion of `detailviewdefs` of the `AOS_Quotes` module, then install the `camunda-example-ui` package. The package will create or ==overwrite== the file `custom/modules/AOS_Quotes/metadata/detailviewdefs.php`;
   * if your installation already had the modification of `detailviewdefs` of the `AOS_Quotes` module, then manually add the approval panel to the `custom/modules/AOS_Quotes/metadata/detailviewdefs.php` file (or `modules/AOS_Quotes/metadata/detailviewdefs.php`);
5. perform the "Recovery" procedure;
6. compile the business process diagram code into `Camunda` (located in the `bpmn` folder):
 
    ```sh
    mvn install
    ```

    Requires java version 1.8;

7. Upload the generated `war` file to the `Camunda` server.
