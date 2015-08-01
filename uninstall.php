<?php
/**
 * Remove settings on plugin delete.
 *
 * WP JV Post Reading Groups Settings Uninstaller
 * @version 1.5
 */

if(!defined('WP_UNINSTALL_PLUGIN')) {
  die('You are not allowed to call this page directly.');
}
//Delete all RGs
delete_option('wp_jv_prg_rg_settings');
//Delete RGs from all posts
delete_post_meta_by_key('wp_jv_post_rg');

//Delete RGs associated to users
$all_user_ids = get_users( array(
								'meta_key'	=>	'wp_jv_user_rg',
								'fields'	=>	'ID'
								) 
	);
foreach ( $all_user_ids as $value ) {
    delete_user_meta( $value, 'wp_jv_user_rg' );
}


?>
