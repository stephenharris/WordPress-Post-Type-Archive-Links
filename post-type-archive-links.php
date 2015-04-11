<?php
defined( 'ABSPATH' ) OR exit;
/*
Plugin Name:  WordPress Post Type Archive Links
Plugin URI:   https://github.com/stephenharris/WordPress-Post-Type-Archive-Links
Description:  Adds a MetaBox to the Appearance > Menu page to add post type archive links
Version:      1.3.1
Author:       Stephen Harris
Author URI:   https://github.com/stephenharris/
Author Email: contact@stephenharris.info
Contributors: Franz Josef Kaiser <wecodemore@gmail.com>, Ryan Urban <ryan@fringewebdevelopment.com>
License:      GPLv3
License URI:  http://www.gnu.org/licenses/gpl.txt
Text Domain:  hptal-textdomain
Domain Path:  /lang/
 
	Copyright 2013 Stephen Harris (contact@stephenharris.info)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	
 */

// Load at the default priority of 10
add_action( 'plugins_loaded', array( new Post_Type_Archive_Links, 'init' ) );

class Post_Type_Archive_Links {
	/**
	 * True if class already inited
	 * @access private
	 * @var bool
	 */
	private $ininited;

	/**
	 * Nonce Value
	 * @const \Post_Type_Archive_Links::NONCE
	 */
	const NONCE = 'hptal_nonce';

	/**
	 * ID of the custom metabox
	 * @const \Post_Type_Archive_Links::METABOXID
	 */
	const METABOXID = 'hptal-metabox';

	/**
	 * ID of the custom post type list items
	 * @const \Post_Type_Archive_Links::METABOXLISTID
	 */
	const METABOXLISTID = 'post-type-archive-checklist';
	
	/**
	 * CPT objects that plugin should handle: having true
	 * 'has_archive', 'publicly_queryable' and 'show_in_nav_menu'
	 * @var array
	 * @access protected
	 */
	protected $cpts;
	
	/**
	 * Handle backward compatibility for removed object variables
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'metabox_id' :
				return self::METABOXID;
			case 'metabox_list_id' :
				return self::METABOXLISTID;
			case 'nonce' :
				return self::NONCE;
			case 'instance' :
				return $this;
		}
	}

	/**
	 * Instantiates the class, add hooks and load domain
	 * @return \Post_Type_Archive_Links
	 * @use \Post_Type_Archive_Links::enable() Add hooks, load domain
	 */
	public function init() {
		if ( ! $this->ininited ) {
			$this->ininited = true;
			$this->enable( dirname( plugin_basename( __FILE__ ) ) );
			
			/**
			This filter allow to access to current class instance
			by calling $hptal = apply_filters( 'post_type_archive_links', NULL );
			No singleton was harmed in the making of this plugin.
			*/
			add_filter( 'post_type_archive_links', array( $this, __FUNCTION__ ) );
		}
		return $this;
	}


	/**
	 * Add plugin hooks and load domain.
	 * @return void
	 * @access private
	 */
	private function enable( $path ) {
		
		load_plugin_textdomain( 'hptal-textdomain' , false , $path . '/lang/' );
		
		add_action( 'admin_init', array( $this, 'get_cpts' ) );
		
		add_action( 'admin_init', array( $this, 'add_meta_box' ), 20 );

		add_filter( 'wp_setup_nav_menu_item',  array( $this, 'setup_archive_item' ) );

		add_filter( 'wp_nav_menu_objects', array( $this, 'maybe_make_current' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'metabox_script' ) );
		
		add_action( "wp_ajax_" . self::NONCE, array( $this, 'ajax_add_post_type' ) );

	}
	
	/**
	 * Remove plugin hooks and unset domain if exists.
	 * @return void
	 */
	public function disable() {
		if ( $this->ininited ) {
			
			if ( isset( $GLOBALS['l10n']['hptal-textdomain'] ) ) {
				unset( $GLOBALS['l10n']['hptal-textdomain'] );
			}
			
			remove_action( 'admin_init', array( $this, 'get_cpts' ) );
		
			remove_action( 'admin_init', array( $this, 'add_meta_box' ), 20 );

			remove_filter( 'wp_setup_nav_menu_item',  array( $this, 'setup_archive_item' ) );

			remove_filter( 'wp_nav_menu_objects', array( $this, 'maybe_make_current' ) );

			remove_action( 'admin_enqueue_scripts', array( $this, 'metabox_script' ) );
		
			remove_action( "wp_ajax_" . self::NONCE, array( $this, 'ajax_add_post_type' ) );
		
		}
	}
	
	/**
	 * Get CPTs that plugin should handle: having true
	 * 'has_archive', 'publicly_queryable' and 'show_in_nav_menu'
	 * @return void
	 */
	public function get_cpts() {
		$cpts = array();
		$has_archive_cps = get_post_types(
			array(
				'has_archive'	=> true,
				'_builtin' => false
			),
			'object'
		);
		foreach ( $has_archive_cps as $ptid => $pt ) {
			$to_show = $pt->show_in_nav_menus && $pt->publicly_queryable;
			if ( apply_filters( "show_{$ptid}_archive_in_nav_menus", $to_show, $pt ) ) {
				$cpts[] = $pt;
			}
		}
		if ( ! empty( $cpts ) ) {
			$this->cpts = $cpts;
		}
	}


	/**
	 * Adds the meta box to the menu page
	 * @return void
	 */
	public function add_meta_box() {
		
		add_meta_box(
			self::METABOXID,
			__( 'Post Type Archives', 'hptal-textdomain' ),
			array( $this, 'metabox' ),
			'nav-menus',
			'side',
			'low'
		);
	}


	/**
	 * Scripts for AJAX call
	 * Only loads on nav-menus.php
	 * @param  string $hook Page Name
	 * @return void
	 */
	public function metabox_script( $hook ) {
		if ( 'nav-menus.php' !== $hook )
			return;
		
		// Do nothing if no CPTs to handle
		if ( empty( $this->cpts ) ) return;

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG) ? '' : '.min';
		
		wp_register_script(
			'hptal-ajax-script',
			plugins_url( "metabox{$suffix}.js", __FILE__ ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( __FILE__ ) . "metabox{$suffix}.js" ),
			true
		);
		wp_enqueue_script( 'hptal-ajax-script' );

		// Add nonce variable
		wp_localize_script(
			'hptal-ajax-script',
			'hptal_obj',
			array(
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( self::NONCE ),
				'metabox_id' => self::METABOXID,
				'metabox_list_id' => self::METABOXLISTID,
				'action'     => self::NONCE
			)
		);
	}


	/**
	 * MetaBox Content Callback
	 * @return string $html
	 */
	public function metabox() {
		
		// Inform user no CPTs available to be shown.
		if ( empty( $this->cpts ) ) {
			echo '<p>' . __( 'No items.' ) . '</p>';
			return;
		}
		
		global $nav_menu_selected_id;

		$html = '<ul id="'. self::METABOXLISTID .'">';
		foreach ( $this->cpts as $pt ) {
			$html .= sprintf(
				'<li><label><input type="checkbox" value ="%s" />&nbsp;%s</label></li>',
				esc_attr( $pt->name ),
				esc_attr( $pt->labels->name )
			);
		}
		$html .= '</ul>';

		// 'Add to Menu' button
		$html .= '<p class="button-controls"><span class="add-to-menu">';
		$html .= '<input type="submit"'. disabled( $nav_menu_selected_id, 0, false ) .' class="button-secondary
			  submit-add-to-menu right" value="'. esc_attr__( 'Add to Menu', 'hptal-textdomain' ) .'" 
			  name="add-post-type-menu-item" id="submit-post-type-archives" />';
		$html .= '<span class="spinner"></span>';
		$html .= '</span></p>';
		
		print $html;
	}

	/**
	 * AJAX Callback to create the menu item and add it to menu
	 * @return string $HTML built with walk_nav_menu_tree()
	 * use \Post_Type_Archive_Links::is_allowed() Check request and return choosen post types
	 */
	public function ajax_add_post_type() {
		$post_types = $this->is_allowed();

		// Create menu items and store IDs in array
		$item_ids = array();
		foreach ( $post_types as $post_type ) {
			$post_type_obj = get_post_type_object( $post_type );

			if( ! $post_type_obj )
				continue;

			$menu_item_data= array(
				 'menu-item-title'  => esc_attr( $post_type_obj->labels->name )
				,'menu-item-type'   => 'post_type_archive'
				,'menu-item-object' => esc_attr( $post_type )
				,'menu-item-url'    => get_post_type_archive_link( $post_type )
			);

			// Collect the items' IDs.
			$item_ids[] = wp_update_nav_menu_item( 0, 0, $menu_item_data );
		}

		// If there was an error die here
		is_wp_error( $item_ids ) AND die( '-1' );

		// Set up menu items
		foreach ( (array) $item_ids as $menu_item_id ) {
			$menu_obj = get_post( $menu_item_id );
			if ( ! empty( $menu_obj->ID ) ) {
				$menu_obj = wp_setup_nav_menu_item( $menu_obj );
				// don't show "(pending)" in ajax-added items
				$menu_obj->label = $menu_obj->title;

				$menu_items[] = $menu_obj;
			}
		}

		// Needed to get the Walker up and running
		require_once ABSPATH.'wp-admin/includes/nav-menu.php';

		// This gets the HTML to returns it to the menu
		if ( ! empty( $menu_items ) ) {
			$args = array(
				'after'       => '',
				'before'      => '',
				'link_after'  => '',
				'link_before' => '',
				'walker'      => new Walker_Nav_Menu_Edit
			);

			echo walk_nav_menu_tree(
				$menu_items,
				0,
				(object) $args
			);
		}

		// Finally don't forget to exit
		exit;

	}


	/**
	 * Is the AJAX request allowed and should be processed?
	 * @return void
	 */
	public function is_allowed() {
		// Capability Check
		! current_user_can( 'edit_theme_options' ) AND die( '-1' );

		// Nonce check
		check_ajax_referer( self::NONCE, 'nonce' );

		// Is a post type chosen?
		$post_types = filter_input_array(
			INPUT_POST,
			array(
				'post_types' => array(
					'filter' => FILTER_SANITIZE_STRING,
					'flags' => FILTER_REQUIRE_ARRAY
				)
			)
		);
		
		empty( $post_types['post_types'] ) AND exit;
		// return post types if chosen
		return array_values( $post_types['post_types'] );
	}


	/**
	 * Assign menu item the appropriate url
	 * @param  object $menu_item
	 * @return object $menu_item
	 */
	public function setup_archive_item( $menu_item ) {
		if ( $menu_item->type !== 'post_type_archive' )
			return $menu_item;

		$post_type = $menu_item->object;
		$menu_item->type_label = __( 'Archive', 'hptal-textdomain' );
		$menu_item->url = get_post_type_archive_link( $post_type );

		return $menu_item;
	}


	/**
	 * Make post type archive link 'current'
	 * @uses   Post_Type_Archive_Links :: get_item_ancestors()
	 * @param  array $items
	 * @return array $items
	 */
	public function maybe_make_current( $items ) {
		foreach ( $items as $item ) {
			if ( 'post_type_archive' !== $item->type )
				continue;

			$post_type = $item->object;
			if (
				! is_post_type_archive( $post_type )
				AND ! is_singular( $post_type )
			)
				continue;

			// Make item current
			$item->current = true;
			$item->classes[] = 'current-menu-item';

			// Loop through ancestors and give them 'parent' or 'ancestor' class
			$active_anc_item_ids = $this->get_item_ancestors( $item );
			foreach ( $items as $key => $parent_item ) {
				$classes = (array) $parent_item->classes;

				// If menu item is the parent
				if ( $parent_item->db_id == $item->menu_item_parent ) {
					$classes[] = 'current-menu-parent';
					$items[ $key ]->current_item_parent = true;
				}

				// If menu item is an ancestor
				if ( in_array( intval( $parent_item->db_id ), $active_anc_item_ids ) ) {
					$classes[] = 'current-menu-ancestor';
					$items[ $key ]->current_item_ancestor = true;
				}

				$items[ $key ]->classes = array_unique( $classes );
			}
		}

		return $items;
	}


	/**
	 * Get menu item's ancestors
	 * @param  object $item
	 * @return array  $active_anc_item_ids
	 */
	public function get_item_ancestors( $item ) {
		$anc_id = absint( $item->db_id );

		$active_anc_item_ids = array();
		while (
			$anc_id = get_post_meta( $anc_id, '_menu_item_menu_item_parent', true )
			AND ! in_array( $anc_id, $active_anc_item_ids )
		)
			$active_anc_item_ids[] = $anc_id;

		return $active_anc_item_ids;
	}
}
