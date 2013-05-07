Contributing
============

Contributing follows the [Wordpress Development FLow](https://github.com/cityindex/wordpress-development-flow)

phpUnit testing
===============
To run the phpUnit tests you need to link this plugin to the wordpress core unit tests.
There are many tutorial on how to do so, but I'm going to recommend my own:
http://david-coombes.com/phpunit-testing-wordpress-plugins-with-wordpress-make/

Configuration
Copy the `tests/config.dist.php` to `tests/config.php` and set the details for your service, eg:
```php
<?php
define('API_CON_TEST_ONLINE', true);

$slug = "my-service/index.php";
$data = array(
	'client_id' => 'xxxxxxxxxx',
	'client_secret' => 'xxxxxxxxxx',
	'redirect_uri' => 'http://example.com/wp-admin/admin-ajax.php?action=api_con_mngr'
);
$options = array(
    'active_plugins' => array('my-service/index.php'),
    'api-connection-manager' => array(
    	'active' => $slug
    	),
    'API_Con_Mngr_Module' => array(
    	$slug => $data
    ),
);

```

Here are some quick, general instructions, to setup phpunit for wordpress:
 - Install [phpUnit](https://github.com/sebastianbergmann/phpunit/)
 - Download WP Core Unit Tests

```bash
$ cd /any/path/you/like
$ svn co https://unit-tests.svn.wordpress.org/trunk wordpress-tests
```
 - Edit `wordpress-tests/wp-tests-config-sample.php` and save as `wordpress-tests/wp-tests-config.php` In the 4th line `define('ABSPATH'..` put the full path to the wordpress blog that your plugin is installed in. Don't forget to fill out the database details, and always use an empty database - the tests will drop ALL tables in the database you choose.  eg:

```php
<?php

/* Path to the WordPress codebase you'd like to test. Add a backslash in the end. */
define( 'ABSPATH', '/var/www/myBlog/public_html/' );
...
// DO NOT use a production database or one that is shared with something else.

define( 'DB_NAME', 'wp_test' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', 'toor' );
define( 'DB_HOST', 'localhost' );
...
```
 - Set the location of the WP Core Unit Tests that you just downloaded with svn in `api-connection-manager/tests/bootstrap.php`, eg:

```php
<?php

//change this to your path
$path = '/any/path/you/like/wordpress-tests/includes/bootstrap.php';
...
```
 - Run tests:

```bash
cd /var/www/myBlog/public_html/wp-content/plugins/api-connection-manager
phpunit
```

Next set up the pre-commit hook so that your database details in `tests/config.php` don't get pushed to the repo:
```bash
cd wp-content/plugins/api-connection-manager
ln -s ./pre-commit.sh ./.git/hooks/pre-commit
```
