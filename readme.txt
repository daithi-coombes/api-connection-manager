=== Plugin Name ===
Contributors: markjaquith, mdawaffe (this should be a list of wordpress.org userid's)
Donate link: http://example.com/
Tags: providers, api, connection, manager
Requires at least: 3.4
Tested up to: 3.4
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manages requests for resources from 3rd party providers using oauth1, oauth2 or
custom api's.

== Description ==

By using modules for each 3rd party provider, such as google, facebook or other
social networks, plugin developers can hook easily into these api's using the
API Connection Manager.

By handling all oauth2, oauth1 or custom api spec, the manager can be used to
store client_id's, client secrets and other params. All these settings can be
set using the service options admin menu.

Also users can see which services they are currently connected to and can revoke
access to any service, or link any service with their account.

== Installation ==

1. Install and active the API Connection Manager plugin
2. Goto the API Connection Manager settings page and activate the modules you
want to use
    
Then to make a request for a remote resource, for example facebook, use the code
`<?php
$module = $API_Connection_Manager->get_service('facebook/index.php');
$res = $module->request(
	"https://graph.facebook.com/me",
	'get'
);
?>`
The above code will get the user details for the current logged in user or
show a log in with facebook link.

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

== Upgrade Notice ==

== Arbitrary section ==

== A brief Markdown Example ==