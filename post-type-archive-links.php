<?php
/*
Plugin Name: Post Type Archive Links
Version: 1.0.1
Description: Adds a metabox to the Appearance > Menu page to add post type archive links
Author: Stephen Harris
Author URI: http://profiles.wordpress.org/users/stephenh1988/
*/

class Harris_Post_Type_Archive_Link{

     //Everything will go here
     public function load(){
          //Hook function to add the metabox to the Menu page
          add_action( 'admin_init', array(__CLASS__,'add_meta_box'));

          // Javascript for the meta box
          add_action( 'admin_enqueue_scripts', array(__CLASS__,'metabox_script') );

          //Ajax callback to create menu item and add it to menu
          add_action('wp_ajax_my-add-post-type-archive-links', array( __CLASS__, 'ajax_add_post_type'));

          //Assign menu item the appropriate url
          add_filter( 'wp_setup_nav_menu_item',  array(__CLASS__,'setup_archive_item') );

          //Make post type archive link 'current'
          add_filter( 'wp_nav_menu_objects', array(__CLASS__,'maybe_make_current'));
     }

     public function add_meta_box() {
          add_meta_box( 'post-type-archives', __('Post Types','my-post-type-archive-links'),array(__CLASS__,'metabox'),'nav-menus' ,'side','low');
     }

     public function metabox( ) {
          global $nav_menu_selected_id;

          //Get post types
          $post_types = get_post_types(array('public'=>true,'_builtin'=>false), 'object');?>

          <!-- Post type checkbox list -->
          <ul id="post-type-archive-checklist">
          <?php foreach ($post_types as $type):?>
               <li><label><input type="checkbox" value ="<?php echo esc_attr($type->name); ?>" /> <?php echo esc_attr($type->labels->name); ?> </label></li>
          <?php endforeach;?>
          </ul><!-- /#post-type-archive-checklist -->

          <!-- 'Add to Menu' button -->
          <p class="button-controls" >
               <span class="add-to-menu" >
                    <input type="submit" id="submit-post-type-archives" <?php disabled( $nav_menu_selected_id, 0 ); ?> value="<?php esc_attr_e('Add to Menu'); ?>" name="add-post-type-menu-item"  class="button-secondary submit-add-to-menu" />
               </span>
          </p>
     <?php
     }

     public function metabox_script($hook) {
          if( 'nav-menus.php' != $hook )
               return;

          //On Appearance>Menu page, enqueue script: 
          wp_enqueue_script( 'my-post-type-archive-links_metabox', plugins_url('/metabox.js', __FILE__),array('jquery'));

          //Add nonce variable
          wp_localize_script('my-post-type-archive-links_metabox','MyPostTypeArchiveLinks', array('nonce'=>wp_create_nonce('my-add-post-type-archive-links')));
     }

   public function ajax_add_post_type(){

          if ( ! current_user_can( 'edit_theme_options' ) )
               die('-1');

          check_ajax_referer('my-add-post-type-archive-links','posttypearchive_nonce');

          require_once ABSPATH . 'wp-admin/includes/nav-menu.php';
          
          if(empty($_POST['post_types']))
               exit;

          //Create menu items and store IDs in array
          $item_ids=array();
          foreach ( (array) $_POST['post_types'] as $post_type) {
               $post_type_obj = get_post_type_object($post_type);

		if(!$post_type_obj)
			continue;

               $menu_item_data= array(
			'menu-item-title' => esc_attr($post_type_obj->labels->name),
			'menu-item-type' => 'post_type_archive',
			'menu-item-object' => esc_attr($post_type),
			'menu-item-url' => get_post_type_archive_link($post_type),
		);

		//Collect the items' IDs. 
		$item_ids[] = wp_update_nav_menu_item(0, 0, $menu_item_data );
          }

          //If there was an error die here
          if ( is_wp_error( $item_ids ) )
               die('-1');

          //Set up menu items
          foreach ( (array) $item_ids as $menu_item_id ) {
               $menu_obj = get_post( $menu_item_id );
               if ( ! empty( $menu_obj->ID ) ) {
                    $menu_obj = wp_setup_nav_menu_item( $menu_obj );
                    $menu_obj->label = $menu_obj->title; // don't show "(pending)" in ajax-added items
                    $menu_items[] = $menu_obj;
               }
          }

          //This gets the HTML to returns it to the menu
          if ( ! empty( $menu_items ) ) {
               $args = array(
                    'after' => '',
                    'before' => '',
                    'link_after' => '',
                    'link_before' => '',
                    'walker' => new Walker_Nav_Menu_Edit,
               );
               echo walk_nav_menu_tree( $menu_items, 0, (object) $args );
          }

          //Finally don't forget to exit
          exit;
     }


     public function setup_archive_item($menu_item){
          if($menu_item->type !='post_type_archive')
               return $menu_item;

          $post_type = $menu_item->object;
          $menu_item->url =get_post_type_archive_link($post_type);

          return $menu_item;
     }

     public function maybe_make_current($items){
          foreach ($items as $item){
               if('post_type_archive' != $item->type)
                    continue;

		$post_type = $item->object;
		if(!is_post_type_archive($post_type)&& !is_singular($post_type))
                    continue;

		//Make item current
		$item->current = true;
		$item->classes[] = 'current-menu-item';

		//Get menu item's ancestors:
		$_anc_id = (int) $item->db_id;
		$active_ancestor_item_ids=array();

		while(( $_anc_id = get_post_meta( $_anc_id, '_menu_item_menu_item_parent', true ) ) &&
				! in_array( $_anc_id, $active_ancestor_item_ids )  )
		{
				$active_ancestor_item_ids[] = $_anc_id;
		}

		//Loop through ancestors and give them 'ancestor' or 'parent' class
		foreach ($items as $key=>$parent_item){
                    $classes = (array) $parent_item->classes;

                    //If menu item is the parent
                    if ($parent_item->db_id == $item->menu_item_parent ) {
                         $classes[] = 'current-menu-parent';
                         $items[$key]->current_item_parent = true;
                    }

                    //If menu item is an ancestor
                    if ( in_array(  intval( $parent_item->db_id ), $active_ancestor_item_ids ) ) {
                         $classes[] = 'current-menu-ancestor';
                         $items[$key]->current_item_ancestor = true;
                    }

                    $items[$key]->classes = array_unique( $classes );
		}

          }
     return $items;
     }



}
Harris_Post_Type_Archive_Link::load();
?>
