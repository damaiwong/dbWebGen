# dbWebGen - Database Web Generator for PHP
This PHP application automatically generates a responsive web app on top of your relational database. The app allows users to
* Create and edit records via web forms, taking into account foreign keys and other constraints
* View stored records along with related records from other tables
* Browse and filter records in a table
* Query the database, visualize the results, and share the visualizations. Currenty the engine offers various visualizations like tables, bar charts, sankeys, timelines, graphs/networks, geomaps and others.

Developers may add custom functionality and extensions to the engine through hook functions in plugins, and admins may exploit an [extensive array of settings](settings.template.php) controlling the engine.

## Requirements
* Webserver running PHP (lowest tested version is 5.3)
* Database server (currently working only with PostgreSQL; lowest tested version is 9.2)
* A database

## Get it Running
* Clone this repository into any folder that is served by your webserver.
* Since this repository contains the app engine only, you need to create another folder that will serve as the actual app folder
* In the app folder, create a PHP file that serves as the main entry point of the app (typically `index.php`). This file is very simple: it must include a definition of the constant `ENGINE_PATH`, which shall define the relative path to app engine folder. The other line in this file is the inclusion of `engine.php` from the app engine folder. Note: if required, you may use `ENGINE_PATH_LOCAL` to define the relative or absolute local file system path to the engine folder, which is used for including `.php` files; the `ENGINE_PATH` is used to point to files in `<script>` or `<link>` tags, so those must be resolveable by the web server.
* Copy `settings.template.php` into your app folder, rename it to `settings.php`, and fill the file with settings that reflect your app and database structure.
* Direct your web browser to the app folder and be happy.

## Example Database and App
An example app using this engine can be seen in the [dbWebGen-demo](https://github.com/eScienceCenter/dbWebGen-demo) repository

## Screenshots
Below are some screenshots from a database app that uses dbWebGen to allow users to work with historic documents from 19th century Oman. Click any thumbnail to view at full resolution.

[![Data in a Table](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/list_documents_th.png)](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/list_documents.png)  
[![Search Filter](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/filter_persons_th.png)](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/filter_persons.png)  
[![View a Record](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/view_document_th.png)](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/view_document.png)  
[![New Record Form](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/new_document_recipient_th.png)](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/new_document_recipient.png)  
[![Edit Record Form](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/edit_document_th.png)](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/edit_document.png)  
[![Query with Network Visualization](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/query_network_th.png)](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/query_network.png)  
[![Query with Map Visualization](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/query_map_th.png)](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/query_map.png)  
[![Responsive Edit](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/edit_responsive_th.png)](https://esciencecenter.github.io/assets/dbWebGen/screenshots/alhamra/edit_responsive.png)

## License
This code is licensed under the MIT license. See the [LICENSE](LICENSE) file.
