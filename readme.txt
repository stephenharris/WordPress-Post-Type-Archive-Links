=== Post Type Archive Link ===
Contributors: stephenharris, F J Kaiser
Tags: post type archives, menu link, archives, navigation, metabox,
Requires at least: 3.3
Tested up to: 3.5
Stable tag: 1.1

Creates a metabox to the Appearance > Menu page to add post type archive links

== Description ==

Post Type Archive LInk creates a metabox on the Appearance > Menu admin page. This lists your custom post types and allows you to add a link to their archive page onto your menu.


== Installation ==

Installation is standard and straight forward.

1. Upload `post-type-archive-links` folder (and all it's contents!) to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. The metabox wll appear at the bottom of your Appearance > Menu


== Frequently Asked Questions ==

= Why are some post types missing? =

The metabox will list only public custom post types


== Changelog ==

= 1.1 =
* Better maintainability (avoid touching JS files)
* Static init now runs during <code>plugins_loaded</code> hook
* Code cleanup and safer names

= 1.0.1 =
* Fixed enqueue bug

= 1.0 =
* Added plug-in
