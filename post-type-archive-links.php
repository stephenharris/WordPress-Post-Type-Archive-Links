<?php
defined( 'ABSPATH' ) OR exit;
/**
 * Plugin Name:  WordPress Post Type Archive Links
 * Version:      1.1
 * Description:  Adds a MetaBox to the Appearance > Menu page to add post type archive links
 * Author:       Stephen Harris <contact@stephenharris.info>
 * Author URI:   https://github.com/stephenharris/
 * Contributors: Franz Josef Kaiser <wecodemore@gmail.com>
 * License:      GPLv3
 * License URI:  http://www.gnu.org/licenses/gpl.txt
 */

// Load at the default priority of 10
add_action( 'plugins_loaded', array( 'HPTAL_MetaBox', 'init' ) );
/**
 * HPTAL_MetaBox
 * Adds a MetaBox to the Appearance > Menu page to add post type archive links
 * @category   Administration User Interface
 * @package    WordPress
 * @author     Stephen Harris <contact@stephenharris.info>
 * @author     Franz Josef Kaiser <wecodemore@gmail.com>
 * @copyright  2012-2013 Stephen Harris
 * @license    http://www.gnu.org/licenses/gpl.txt GPLv3
 * @link       https://github.com/stephenharris/WordPress-Post-Type-Archive-Links
 */
class HPTAL_MetaBox
{
	/**
	 * Instance of the class
	 * @static
	 * @access protected
	 * @var    object
	 */
	protected static $instance;

	/**
	 * Nonce Value
	 * @var string
	 */
	public $nonce = 'hptal_nonce';

	/**
	 * ID of the HTML element
	 * @var string
	 */
	public $metabox_id = 'post-type-archive-checklist';


	/**
	 * Instantiates the class
	 * @return object $instance
	 */
	public static function init()
	{
		is_null( self :: $instance ) AND self :: $instance = new self;
		return self :: $instance;
	}


	/**
	 * Constructor.
	 * @return \HPTAL_MetaBox
	 */
	public function __construct()
	{
		add_action( 'admin_head-nav-menus.php', array( $this, 'setup_admin_hooks' ) );

		add_filter( 'wp_setup_nav_menu_item',  array( $this, 'setup_archive_item' ) );

		add_filter( 'wp_nav_menu_objects', array( $this, 'maybe_make_current' ) );
		add_action( 'admin_init', array( $this, 'add_meta_box' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'metabox_script' ) );

		add_action( "wp_ajax_{$this->nonce}", array( $this, 'ajax_add_post_type' ) );
	}


	/**
	 * Adds all callbacks to the appropriate filters & hooks in the admin UI.
	 * Only loads on the admin UI nav menu management page.
	 * @return void
	 */
	public function setup_admin_hooks()
	{
		add_action( 'admin_init', array( $this, 'add_meta_box' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'metabox_script' ) );

		add_action( "wp_ajax_{$this->nonce}", array( $this, 'ajax_add_post_type' ) );
	}


	/**
	 * Adds the meta box to the menu page
	 * @return void
	 */
	public function add_meta_box()
	{
		add_meta_box(
			 'hptal-metabox'
			,__( 'Post Types', 'hptal-textdomain' )
			,array( $this, 'metabox' )
			,'nav-menus'
			,'side'
			,'low'
		);
	}


	/**
	 * Scripts for AJAX call
	 * Only loads on nav-menus.php
	 * @param  string $hook Page Name
	 * @return void
	 */
	public function metabox_script( $hook )
	{
		if ( 'nav-menus.php' !== $hook )
			return;

		wp_register_script(
			 'hptal-ajax-script'
			,plugins_url( 'metabox.js', __FILE__ )
			,array( 'jquery' )
			,filemtime( plugin_dir_path( __FILE__ ).'metabox.js' )
			,true
		);
		wp_enqueue_script( 'hptal-ajax-script' );

		// Add nonce variable
		wp_localize_script(
			 'hptal-ajax-script'
			,'hptal_obj'
			,array(
				 'ajaxurl'    => admin_url( 'admin-ajax.php' )
				,'nonce'      => wp_create_nonce( $this->nonce )
				,'metabox_id' => $this->metabox_id
			 )
		);
	}


	/**
	 * MetaBox Content Callback
	 * @return string $html
	 */
	public function metabox()
	{
		global $nav_menu_selected_id;

		// Get post types
		$post_types = get_post_types(
			 array(
				 'public'   => true
				,'_builtin' => false
			 )
			,'object'
		);

		// #{$metabox_id}
		$html = "<ul id='{$metabox_id}'>";
		foreach ( $post_types as $pt )
		{
			$html .= sprintf(
				 '<li><label><input type="checkbox" value ="%s" />&nbsp;%s</label></li>'
				,esc_attr( $pt->name )
				,esc_attr( $pt->labels->name )
			);
		}
		$html .= '</ul>';

		$html .= sprintf(
			 '<img class="waiting" src="%s" alt="Loading">'
			,admin_url( '/images/wpspin_light.gif' )
		);

		// 'Add to Menu' button
		$html .= sprintf(
			 '<p class="button-controls"><span class="add-to-menu">%s</span></p>'
			,get_submit_button(
				 esc_attr__( 'Add to Menu' )
				,'button-secondary submit-add-to-menu'
				,'add-post-type-menu-item'
				,false
				,array(
					# 'id'       => 'submit-post-type-archives'
					#,'onClick'  => 'return false;'
					#,'disabled' => disabled( $nav_menu_selected_id, 0, false )
				 )
			 )
		);
		print $html;
	}


	/**
	 * AJAX Callback to create the menu item and add it to menu
	 * @return string $HTML built with walk_nav_menu_tree()
	 */
	public function ajax_add_post_type()
	{
		$this->is_allowed();

		// Create menu items and store IDs in array
		$item_ids = array();
		foreach ( array_values( $_POST['post_types'] ) as $post_type )
		{
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
		foreach ( (array) $item_ids as $menu_item_id )
		{
			$menu_obj = get_post( $menu_item_id );
			if ( ! empty( $menu_obj->ID ) )
			{
				$menu_obj = wp_setup_nav_menu_item( $menu_obj );
				// don't show "(pending)" in ajax-added items
				$menu_obj->label = $menu_obj->title;

				$menu_items[] = $menu_obj;
			}
		}

		// Needed to get the Walker up and running
		require_once ABSPATH.'wp-admin/includes/nav-menu.php';

		// This gets the HTML to returns it to the menu
		if ( ! empty( $menu_items ) )
		{
			$args = array(
				 'after'       => ''
				,'before'      => ''
				,'link_after'  => ''
				,'link_before' => ''
				,'walker'      => new Walker_Nav_Menu_Edit
			);

			echo walk_nav_menu_tree(
				 $menu_items
				,0
				,(object) $args
			);
		}

		// Finally don't forget to exit
		exit;
	}


	/**
	 * Is the AJAX request allowed and should be processed?
	 * @return void
	 */
	public function is_allowed()
	{
		// Capability Check
		! current_user_can( 'edit_theme_options' ) AND die( '-1' );

		// Nonce check
		check_ajax_referer( $this->nonce, 'hptal_nonce_query_arg' );

		// Is a post type chosen?
		empty( $_POST['post_types'] ) AND exit;
	}


	/**
	 * Assign menu item the appropriate url
	 * @param  object $menu_item
	 * @return object $menu_item
	 */
	public function setup_archive_item( $menu_item )
	{
		if ( $menu_item->type !== 'post_type_archive' )
			return $menu_item;

		$post_type = $menu_item->object;
		$menu_item->url = get_post_type_archive_link( $post_type );

		return $menu_item;
	}


	/**
	 * Make post type archive link 'current'
	 * @uses   HPTAL_MetaBox :: get_item_ancestors()
	 * @param  array $items
	 * @return array $items
	 */
	public function maybe_make_current( $items )
	{
		foreach ( $items as $item )
		{
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
			foreach ( $items as $key => $parent_item )
			{
				$classes = (array) $parent_item->classes;

				// If menu item is the parent
				if ( $parent_item->db_id == $item->menu_item_parent )
				{
					$classes[] = 'current-menu-parent';
					$items[ $key ]->current_item_parent = true;
				}

				// If menu item is an ancestor
				if ( in_array( intval( $parent_item->db_id ), $active_anc_item_ids ) )
				{
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
	public function get_item_ancestors( $item )
	{
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