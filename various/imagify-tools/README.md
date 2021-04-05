# Imagify Tools

Imagify Tools is a set of tools to help develop and debug the Imagify WordPress plugin.

## Installation

It can be installed as a normal plugin (on multisite it can be activated from the network or by site). It can also be installed as a Must Use plugin: drop the folder in the *mu-plugins* folder, then move the file *imagify-tools.php* one level down: from within the plugin to within the *mu-plugins* folder (the file and the plugin folder are now in the same folder).

## Uninstallation

The plugin stores data in the database, so it needs to be properly uninstalled.  
If installed as a MU plugin, use the link provided at the top of the plugin's pages.  
If installed as a normal plugin, you can uninstall it like any other plugins, or use the link at the top of the plugin's pages (it's faster).

## Available tools

### Display post metas on attachment edition page

Those metas are displayed in 3 groups: WordPress mandatory metas (metas created by WP, nothing can work without them), metas from Imagify, metas from Amazon S3 plugin, other metas.  
Some rows may have a red background if a problem is detected.

### Infos page

A page that displays various information about the website, the server, and the configuration is created.  
Some rows may have a red background if a problem is detected.  
HTTP requests are cached for half an hour: each cache can be cleared thanks to a button.  
Imagify sometimes uses "background processing" (non-blocking http requests). A button "Make Optimization Non Async" is available to allowing us to log the result of those requests (but they can easily return a timeout error).

### Logs page

Few things are logged, and then are displayed on this page: external requests to our servers, internal requests to *admin-ajax.php*, Imagify settings changes.  
Logs can be downloaded or deleted from here.

### Tests area

At the bottom of the Infos and Logs page, an area dedicated to code tests can be displayed.  
How to use it: at the root of the plugin folder there's a file named **tests.php**: simply add some code inside, like:

	print_r( $wp_filter['http_api_debug']->callbacks );

Then reload one of the plugin's page and you'll see the result at the bottom of the page, wrapped in `<pre>` tag.
