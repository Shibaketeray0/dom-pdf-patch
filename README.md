# PDF Generator

This module intend to generate a simple way to generate PDF Files...

For now the module include two features:

- A service to generate pdf files that will be used in a controller easyly.
- A views display plugin to generate a pdf file from any view.

To use the display you only need to generate a a display of type PDF on your
views, select a format for the output and configure the path of the view.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/pdf_generator).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/pdf_generator).


## Requirements

The module depends of

- [DomPDF library](https://github.com/dompdf/dompdf)


## Service

To use the service you only must get it with the service container or add it
to the dependency injection in controllers, forms or any other plugin.

`$service = \Drupal::service('pdf_generator.dompdf_generator');`

`$response = $service->response($title, $content);`

Where the title will be any text and the colugntent will be any render array.
Use it in any routing controller and use it as result of the page method.

The response method has some options to configure the output of the PDF file.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Maintainers

- Juan Natera - [jncruces](https://www.drupal.org/u/jncruces)
