# Post Type Archive Link #
**Contributors:** stephenharris, F J Kaiser, ryancurban, giuseppe.mazzapica  
**Tags:** post type archives, menu link, archives, navigation, metabox, administration user interface  
**Requires at least:** 3.3  
**Tested up to:** 4.0  
**Stable tag:** 1.3.0  
**License:** GPLv3 or later  
**License URI:** http://www.gnu.org/licenses/gpl.txt  

Creates a metabox to the Appearance > Menu page to add custom post type archive links

## Description ##

Post Type Archive Link creates a metabox on the Appearance > Menu admin page. 
This lists your custom post types and allows you to add links to each archive page in your WordPress menus.

The plug-in uses WordPress' default menu classes for current pages, current page parent and current page ancestor.
 
By default all post types with archives (and not registered by core) are available for adding to your menu. 
You can forcibly revent a particlar post type from appearing using the `show_{$posttype}_archive_in_nav_menus` hook.


## Installation ##

Installation is standard and straight forward.

1. Upload `WordPress-Post-Type-Archive-Links` folder (and all it's contents!) to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. The metabox will appear at the bottom of your Appearance > Menu


## Frequently Asked Questions ##

### I can't see in the 'post type' metabox on the Apperance > Menus screen ###

View the "screen options" (top right), and ensure that "Post Type Archives" is checked.


### Why are some post types missing? ###

The metabox will only list custom post types registered with non-falsey `has_archive`, `publicly_queryable` or `show_in_vav_menus`.

CPTs having true `has_archive' but false `publicly_queryable` and/or `show_in_vav_menus` can be shown using `show_{$cpt_slug}_archive_in_nav_menus` filter hook.


## Screenshots ##

### 1. Custom post types admin menu metabox ###
![Custom post types admin menu metabox](http://s.w.org/plugins/post-type-archive-links/screenshot-1.png)

### 2. Custom post types added to your menu ###
![Custom post types added to your menu](http://s.w.org/plugins/post-type-archive-links/screenshot-2.png)

### 3. Custom post type 'Clients' in front-end menu with WordPress menu classes and current item styles ###
![Custom post type 'Clients' in front-end menu with WordPress menu classes and current item styles](http://s.w.org/plugins/post-type-archive-links/screenshot-3.png)



## Changelog ##

### 1.3 ###
* Make submit button available for translation. Thanks to [@antwortzeit](https://github.com/antwortzeit).
* Removed hooks from constructor, allowed plugin disabling: removing all hooks and text domain
* Introduced "post_type_archive_links" filter hook to get an instance of plugin class
* Hide CPTs having 'has_archive' true, but 'publicly_queryable' and/or 'show_in_vav_menus' set to false
* Introduced "show_{$cpt_slug}_archive_in_nav_menus" filter to force CPTs be added on metabox
* Show "No items." when there are no CPTs available
* Tested up to 4.0
* Added Italian language. Thanks to [@giuseppe.mazzapica](http://gm.zoomlab.it).
* Updated readme

### 1.2 ###
* Use has_archive rather than public. [See #13](https://github.com/stephenharris/WordPress-Post-Type-Archive-Links/issues/13)
* Fixes bug where "disabled" is printed if no menu has been created.
* Tested up to 3.7.1
* Added German language. Thanks to [@mcguffin](https://github.com/mcguffin).

### 1.1 ###
* Fixed a couple of notices that displayed with debug on
* Better maintainability (avoid touching JS files)
* Static init now runs during <code>plugins_loaded</code> hook
* Code cleanup and safer names

### 1.0.1 ###
* Fixed enqueue bug

### 1.0 ###
* Added plug-in
