<?php
/**
* Plugin Name: Member Control Hooks
* Plugin URI: https://inauditas.com/
* Description: Hooks for wp_member and Download Monitor plugin, to set permission levels. Needs also groups plugins in order to work
* Version: 1.0 
* Author: Maddish - Inauditas
* Author URI: https://inauditas.com/
* License: GPL v3 
*/
if ( !defined( 'ABSPATH' ) ) { 
        exit;
}



/* Create Plugin's Option Page */
function mCHooks_register_settings() {
   add_option( 'mCHooks_option_level_text', 'No tienes permisos suficientes para acceder a este contenido');
   register_setting( 'mCHooks_options_group', 'mCHooks_option_level_text', 'mCHooks_callback' );
}
add_action( 'admin_init', 'mCHooks_register_settings' );

function mCHooks_register_options_page() {
  add_options_page('mCHooks Settings', 'Member Control Hooks', 'manage_options', 'mCHooks', 'mCHooks_options_page');
}
add_action('admin_menu', 'mCHooks_register_options_page');

function mCHooks_options_page()
{
?>
  <div>
  <h2>Member Control Hooks Setting</h2>
  <form method="post" action="options.php">
  <?php settings_fields( 'mCHooks_options_group' ); ?>
  <h3>Textos personalizados</h3>
  <p>Personaliza el texto a mostrar para usuarios con privilegios insuficientes</p>
  <table>
  <tr valign="top">
  <th scope="row"><label for="mCHooks_option_level_text">Inserta el texto que quieres mostrar</label></th>
  <td><textarea style="width:100%;rows="20" cols="70" id="mCHooks_option_level_text" name="mCHooks_option_level_text" value="<?php echo get_option('mCHooks_option_level_text'); ?>"><?php echo get_option('mCHooks_option_level_text'); ?></textarea></td>
  </tr>
  </table>
  <?php  submit_button(); ?>
  </form>
  </div>
<?php
} 

/* Remove all these actions from init method of class Groups_Post_Access (groups plugin)
* included in plugins/groups/lib/access/class-groups-post-access.php 
* The groups plugins makes a redirect to 404 for all pages that have a restriction.
* We use the method from the wp-members plugin to restrict acces (show a login form)
* but use the groups plugin feature for groups creation and meta values for posts and pages access 
* wp-members plugin + hook from this same plugin,  will be in charge of restricting access 
* based on groups
*/
        // post access
       $class_name = 'Groups_Post_Access'; 
       // add_filter( 'posts_where', array( __CLASS__, 'posts_where' ), 10, 2 );
		remove_filter( 'posts_where', array( $class_name, 'posts_where' ),10, 2 );
       // add_filter( 'get_pages', array( __CLASS__, 'get_pages' ), 1 );
		remove_filter( 'get_pages', array( $class_name, 'get_pages' ),1);
       //add_filter( 'the_posts', array( __CLASS__, 'the_posts' ), 1, 2 );
		remove_filter( 'the_posts', array( $class_name, 'the_posts' ), 1, 2 );
        // If we had a get_post filter https://core.trac.wordpress.org/ticket/12955

        // add_filter( 'get_post', ... );
        //add_filter( 'wp_get_nav_menu_items', array( __CLASS__, 'wp_get_nav_menu_items' ), 1, 3 );
		remove_filter( 'wp_get_nav_menu_items', array( $class_name, 'wp_get_nav_menu_items' ), 1, 3 );
        // content access
        //add_filter( 'get_the_excerpt', array( __CLASS__, 'get_the_excerpt' ), 1 );
		remove_filter( 'get_the_excerpt', array( $class_name, 'get_the_excerpt' ), 1 );
        //add_filter( 'the_content', array( __CLASS__, 'the_content' ), 1 );
		remove_filter( 'the_content', array( $class_name, 'the_content' ), 1 );
        // edit & delete post
        //add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
		remove_filter( 'map_meta_cap', array( $class_name, 'map_meta_cap' ), 10, 4 );

        //add_action( 'groups_deleted_group', array( __CLASS__, 'groups_deleted_group' ) );
		remove_action( 'groups_deleted_group', array( $class_name, 'groups_deleted_group' ) );
        //add_filter( 'wp_count_posts', array( __CLASS__, 'wp_count_posts' ), 10, 3 );
		remove_filter( 'wp_count_posts', array( $class_name, 'wp_count_posts' ), 10, 3 );
        //add_filter( "rest_prepare_{$post_type}", array( __CLASS__, 'rest_prepare_post' ), 10, 3 );
        $post_types = Groups_Post_Access::get_handles_post_types();
        if ( !empty( $post_types ) ) { 
            foreach( $post_types as $post_type => $handles ) { 
                if ( $handles ) { 
                    remove_filter( "rest_prepare_{$post_type}", array( $class_name, 'rest_prepare_post' ), 10, 3 );
                }
            }
        }


        // adjacent posts
       // add_filter( 'get_previous_post_where', array( __CLASS__, 'get_previous_post_where' ), 10, 5 );
        //add_filter( 'get_next_post_where', array( __CLASS__, 'get_next_post_where' ), 10, 5 );
        remove_filter( 'get_previous_post_where', array( $class_name, 'get_previous_post_where' ), 10, 5 );
        remove_filter( 'get_next_post_where', array( $class_name, 'get_next_post_where' ), 10, 5 );


function show_privilege_message() {
	$content = get_option('mCHooks_option_level_text');
	return $content;
}
		
		

/*
* Add filter to wpmem_securify to add control acces based on custom metas
* Control access for posts/pages
*/
function check_user_groups($content){
	global $post;
	//$content = get_the_content($post->ID);
	// check if post/page is restricted
	$group_ids = Groups_Post_Access::get_read_group_ids( $post->ID );
	// Post is restricted by groups
	if ( count( $group_ids ) > 0 ) { 
		// Get groups user belong to (also for inheritance)
		$groups_user = new Groups_User( get_current_user_id() );
		$user_group_ids_deep = $groups_user->group_ids_deep;
		$result = !empty(array_intersect($group_ids, $user_group_ids_deep));
		if (!$result){ 
			$content = show_privilege_message();	
		}
	}
	//$content = apply_filters ('the_content', $content);
	return $content;

}


/* Add filter to  add_filter( 'dlm_can_download', array( $this, 'check_members_only' ), 10, 2 );
* To add custom filter for downloads
*/

function check_download_permissions($can_download,$download) {
	// Admins can access all content
	if (!current_user_can('administrator')){
		$target_post = $download->id;
		$group_ids = Groups_Post_Access::get_read_group_ids( $target_post );
		// redirect to no access page
		if ( count( $group_ids ) > 0 ) { 
			// Get groups user belong to (also for inheritance)
			$groups_user = new Groups_User( get_current_user_id() );
			$user_group_ids_deep = $groups_user->group_ids_deep;
			$result = !empty(array_intersect($group_ids, $user_group_ids_deep));
			if (!$result) {
				$can_download = false;
			}
		}
	} // end if is not admin
	

	return $can_download;

}   

/* Check if required plugins are active before using their functions to avoid fatal errors */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if ( is_plugin_active('download-monitor/download-monitor.php') && is_plugin_active('wp-members/wp-members.php') && is_plugin_active('groups/groups.php')){
add_filter('wpmem_securify','check_user_groups');
add_filter( 'dlm_can_download', 'check_download_permissions', 10, 2);
}
