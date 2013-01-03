api-connection-manager
======================

Manages connections and requests to 3rd party providers or services.


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
```php
$data = API_Connection_Manager::request(
	'service-slug',
	array()		//this array is the same format as the WP_HTTP class
);
```