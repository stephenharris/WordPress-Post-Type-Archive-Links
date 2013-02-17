=== Post Type Archive Link ===
Contributors: stephenharris, F J Kaiser, ryancurban
Tags: post type archives, menu link, archives, navigation, metabox, administration user interface
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 1.1
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl.txt

Creates a metabox to the Appearance > Menu page to add custom post type archive links

== Description ==

Post Type Archive Link creates a metabox on the Appearance > Menu admin page. This lists your custom post types and allows you to add links to each archive page in your WordPress menus.

The great thing about this plugin is it integrates fully with the WordPress menus functionality, and so you will notice that your custom post type archive links that are added to your menus take advantage of the typical menu classes aded by WordPress, including the current page class.


== Installation ==

Installation is standard and straight forward.

1. Upload `WordPress-Post-Type-Archive-Links` folder (and all it's contents!) to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. The metabox will appear at the bottom of your Appearance > Menu


== Frequently Asked Questions ==

= Why are some post types missing? =

The metabox will only list public custom post types


== Screenshots ==

1. Custom post types admin menu metabox
2. Custom post types added to your menu
3. Custom post type 'Clients' in front-end menu with WordPress menu classes and current item styles


== Changelog ==

= 1.1 =
* Fixed a couple of notices that displayed with debug on
* Better maintainability (avoid touching JS files)
* Static init now runs during <code>plugins_loaded</code> hook
* Code cleanup and safer names

= 1.0.1 =
* Fixed enqueue bug

= 1.0 =
* Added plug-in
