API Connection Manager
======================

Manages connections and requests to 3rd party providers or services.

[![Build Status](http://david-coombes.com:8080/jenkins/buildStatus/icon?job=API-Connection-Manager)](http://david-coombes.com:8080/jenkins/job/API-Connection-Manager/)

Contributing
============
See [Contributing.md](https://github.com/daithi-coombes/api-connection-manager/blob/master/Contributing.md)

Installation
============

Navigate to your wp-plugins folder
run
```
git clone git@github.com:david-coombes/api-connection-manager.git
```

To install the module files, whilst in your wp-plugins folder
run
```
git clone git@github.com:david-coombes/api-con-mngr-modules.git
```

Activate the API Connection Manager in your dashboard plugins page

Usage
=====
Make sure the class-api-connection-manager.php file is included at the top of your plugin.
```php
require_once( WP_PLUGIN_DIR  . "/api-connection-manager/index.php");
```

To make requests to a service
```php
$data = API_Connection_Manager::request(
	'service-slug',
	array()		//this array is the same format as the WP_HTTP class
);
```

If you need to print login buttons for a service, you will need to provide a callback function as well
```php
global $API_Connection_Manager;

//get the module object
$module = $API_Connection_Manager->get_service('slug/index.php');
print $module->get_login_button( __FILE__, array('my_callback_class', 'parse_dto') );
```
