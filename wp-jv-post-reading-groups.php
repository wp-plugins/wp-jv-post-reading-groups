<?php
/**
 * Plugin Name: WP JV Post Reading Groups
 * Plugin URI: http://janosver.com/projects/wordpress/wp-jv-post-reading-groups
 * Description: Grant read-only permission for selected users (with no administrator role) on selected private posts 
 * Version: 1.5
 * Author: Janos Ver 
 * Author URI: http://janosver.com
 * License: GPLv2 or later
 */

//No direct access allowed to plugin php file
if(!defined('ABSPATH')) {
	die('You are not allowed to call this page directly.');
}
 

/************************************************************************************************************/ 
/* Adds a Reading Groups metabox to Edit Post screen */
/************************************************************************************************************/
function wp_jv_prg_add_rg_meta_box_head() {
	add_meta_box('wp_jv_prg_sectionid','WP JV Reading Groups','wp_jv_prg_add_rg_meta_box', 'post','side','high');
}
add_action( 'add_meta_boxes', 'wp_jv_prg_add_rg_meta_box_head' );

//Prints the box content
function wp_jv_prg_add_rg_meta_box( $post ) {

	// Add an nonce field so we can check for it later
	wp_nonce_field( 'wp_jv_prg_meta_box', 'wp_jv_prg_meta_box_nonce' );

	//Get all available RGs from database
	$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
	//Get current user's permissions
	$wp_jv_post_rg=get_post_meta($post->ID, 'wp_jv_post_rg',true);
	
	//Echo checkboxes and tick saved selections	
	if (empty($wp_jv_prg_rg_settings)) {
		echo __('Create some groups first at','wp-jv-post-reading-groups');
		echo ' <a href="options-reading.php">';
		echo __('Settings -> Reading','wp-jv-post-reading-groups');
		echo '</a>';
	} 
	else {
	     echo __( 'Select who can read this post', 'wp-jv-post-reading-groups');
		 echo '<br>';
		 
		foreach ($wp_jv_prg_rg_settings as $key => $value) {	
			echo '<input type="checkbox" name="wp-jv-reading-group-field-'. $key. '" value="'. $wp_jv_prg_rg_settings[$key]. '" ';
			if (!empty($wp_jv_post_rg) && in_array($key, $wp_jv_post_rg,true)) { echo 'checked="checked"';} 
			echo '/>'. $wp_jv_prg_rg_settings[$key]. '<br>';
			}
		}				
}

//When the post is saved, saves our custom data
function wp_jv_prg_save_rg_meta_box( $post_id ) {

	// Verify this came from our screen and with proper authorization

	// Check if our nonce is set.
	if ( ! isset( $_POST['wp_jv_prg_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['wp_jv_prg_meta_box_nonce'], 'wp_jv_prg_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}
	$NewRG=null;
	$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
	if (!empty($wp_jv_prg_rg_settings)) {	
		foreach ($wp_jv_prg_rg_settings as $key => $value) {	
			if (isset($_POST['wp-jv-reading-group-field-'. $key])) {$NewRG[]=$key;}
			}	
	}
	// Update reading groups custom field in the database.
	update_post_meta( $post_id, 'wp_jv_post_rg', $NewRG );	
}
add_action( 'save_post', 'wp_jv_prg_save_rg_meta_box' );


/************************************************************************************************************/
/* Creating Reading Groups @ Settings-> Reading */
/************************************************************************************************************/

//Load WP_List_Table if not loaded
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/*Start class WP_JV_PRG_List_Table*/
class WP_JV_PRG_List_Table extends WP_List_Table {

	function __construct( $args = array() ){		
		$args = wp_parse_args($args,  array(
			'singular'  => __( 'Reading Group','wp-jv-post-reading-groups'),     //singular name of the listed records
			'plural'    => __( 'Reading Groups','wp-jv-post-reading-groups' ),   //plural name of the listed records
			'ajax'      => false
			));			
	}
	
	function get_columns(){
		$columns = array('reading_group' => __('Reading Group','wp-jv-post-reading-groups'));
		return $columns;
	}
	
	function column_default( $item, $column_name ) {
		switch( $column_name ) { 
		case 'reading_group':
		  return $item[ $column_name ];
		}
	}	

	function prepare_items() {		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);	  		
		
		$wp_jv_prg_reading_groups_stored=get_option('wp_jv_prg_rg_settings');

		if (!empty($wp_jv_prg_reading_groups_stored)) {
		foreach ($wp_jv_prg_reading_groups_stored as $key=>$value) {
		$wp_jv_prg_reading_groups_to_display[] = array('reading_group'=>$wp_jv_prg_reading_groups_stored[$key]);
		}
		$this->items=$wp_jv_prg_reading_groups_to_display;
		}	
	}
		
	function display_tablenav($which){
		//Leave it empty to remove tablenav
	}
	
	function bulk_actions($which = ''){		
		//
	}

	//Refresh table with AJAX (no page refresh)
	function ajax_response() {	
		$this->prepare_items();		 	
		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$rows = $this->display_rows();
		}
		else
			{$rows = $this->display_rows_or_placeholder();}
		$rows = ob_get_clean();
		$response = array( 'rows' => $rows );					
		die(json_encode( $response ));
	}
	
	//Add row actions	
	function column_reading_group( $item ) {		
		$data=get_option('wp_jv_prg_rg_settings');
		$ItemKey=array_search($item['reading_group'],$data);			
		$renamediv='<div class="RenameDiv-'. $ItemKey. '"></div>';
		$itemdiv='<div class="ItemDiv-'. $ItemKey. '">'. $item['reading_group']. '</div>';
		$actions = array(
			//Hidden input box				
			//Edit link
			'edit'		=> sprintf('<a class="lnkEdit" data-RG="'. 
			$ItemKey. 
			'" href="'. 
			wp_nonce_url( admin_url('options-reading.php?action=edit&rg='. $ItemKey),'edit'. $ItemKey,'jv_prg_nonce')
			. '">'.
			__('Rename','wp-jv-post-reading-groups').
			'</a>'),
			//Delete link
			'delete'		=> sprintf('<a class="lnkDelete" href="'. 
			wp_nonce_url( admin_url('options-reading.php?action=delete&rg='. $ItemKey),'delete'. $ItemKey,'jv_prg_nonce').
			'">'.
			__('Delete','wp-jv-post-reading-groups').
			'</a>')				
			);				
		return sprintf('%1$s %2$s %3$s', $renamediv, $itemdiv, $this->row_actions( $actions ));			
	}
} 
/*End class WP_JV_PRG_List_Table*/


//Initialize js methods
function wp_jv_prg_load_js_methods() {
   wp_register_script( 'wp_jv_prg_script', plugin_dir_url(__FILE__).'wp-jv-post-reading-groups.min.js', array('jquery') );
   wp_register_style( 'wp_jv_rg_styles',plugin_dir_url(__FILE__).'wp-jv-post-reading-groups.css');
   //Make sure we can use jQuery
   wp_enqueue_script( 'jquery' );   
 
    //support languages
   load_plugin_textdomain('wp-jv-post-reading-groups', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    
   //Load script
   wp_enqueue_script( 'wp_jv_prg_script' );
   //Load style
   wp_enqueue_style('wp_jv_rg_styles');
   //Improve security
   $nonce_array = array( 'wp_jv_rg_nonce' =>  wp_create_nonce ('wp_jv_rg_nonce') );
   wp_localize_script( 'wp_jv_prg_script', 'wp_jv_prg_obj', $nonce_array );
}
add_action( 'init', 'wp_jv_prg_load_js_methods' );


//Refresh WP-List-Table (AJAX call handler)
function wp_jv_prg_refresh_rg_list() {	
	$wp_jv_prg_reading_groups_table = new WP_JV_PRG_List_Table();	
	$wp_jv_prg_reading_groups_table->ajax_response();		
}
add_action('wp_ajax_wp_jv_prg_refresh_rg_list', 'wp_jv_prg_refresh_rg_list');


//Add new Reading Group to database (AJAX call handler)    
function wp_jv_prg_add_new_rg_to_db() {		    
   //Avoid being easily hacked
	if (!isset($_POST['wp_jv_rg_nonce']) || !wp_verify_nonce($_POST['wp_jv_rg_nonce'],'wp_jv_rg_nonce')) {
		$result=array('error'	   => true,
			 		   'error_msg'  => 'Something went wrong',
					   'error_code' => 'F-01');		
		header('Content-Type: application/json');
		die(json_encode($result));
	}		
	
	//No value OR empty -> no data saved
	if(isset($_POST['newrg'])) { 
		$newRG = sanitize_text_field($_POST['newrg']);
		if (!empty($newRG)) {
			$data = get_option('wp_jv_prg_rg_settings');
			if (!array_search($newRG,$data) ) {
				$data[] =$newRG;
				update_option('wp_jv_prg_rg_settings',$data);
				$result=array('error'	   => false,
							  'error_msg'  => 'Reading Group: '. $newRG. ' added',
							  'error_code' => null);
				}
				else {
					  $result=array('error'	   => true,
									'error_msg'  => __('Reading Group name','wp-jv-post-reading-groups').' "'. $newRG. '" '.__('already exists.','wp-jv-post-reading-groups'),
									'error_code' => 'P-01');			
				
					}
		} else {
				$result=array('error'	   => true,
					  'error_msg' => __('Please specify a valid Reading Group name.','wp-jv-post-reading-groups'),
					  'error_code' => 'P-02');			
			}	    		
	}
	else $result=array('error'	    => true,
			 		   'error_msg'  => 'Something went wrong',
					   'error_code' => 'F-02');			
	//to debug uncomment the following 3 lines	
	/*
	$result=array_merge($result,array('action'		=>	'add',
					'newRG'	=>	sanitize_text_field($_POST['newrg'])
					));	
	*/
	header('Content-Type: application/json');
	die(json_encode($result));	
}
add_action('wp_ajax_wp_jv_prg_add_new_rg_to_db','wp_jv_prg_add_new_rg_to_db');



//Rename existing Reading Group in database (AJAX call handler)
//SaveRenamedRGtoDB
function wp_jv_prg_save_renamed_rg_to_db() {		
    //RGToRename = Existing RG ID
	//NewRGName = New RG name
	
	//No value OR empty -> no data saved
	$NewRGName = sanitize_text_field($_POST['NewRGName']);
	$RGToRename = $_POST['RGToRename'];
	if(isset($_POST['RGToRename']) && isset($_POST['NewRGName'])) { 
		if (!empty($NewRGName)) {		
			$data = get_option('wp_jv_prg_rg_settings');
			if (!empty($data[$RGToRename]) ) {
				if (!array_search($NewRGName,$data,true) && array_search($NewRGName,$data,true) !==0 ) {					
					$data[$RGToRename] = $NewRGName;
					update_option('wp_jv_prg_rg_settings',$data);							
					$result=array('error'	   => false,
								  'error_msg'  => 'Reading Group: '. $data[$RGToRename]. ' renamed to'. $NewRGName,
								  'error_code' => null);
					}
					else {
							$result=array('error'	   => true,
										'error_msg'  => __('Reading Group','wp-jv-post-reading-groups').' "'. $NewRGName. '" '.__('already exists.','wp-jv-post-reading-groups'),
										'error_code' => 'P-03');												
					
						}
				}	
				else {
						$result=array('error'	   => true,
									'error_msg'  => __('Reading Group','wp-jv-post-reading-groups').' "'. $data[$RGToRename]. '" '.__('does not  exists.','wp-jv-post-reading-groups'),
									'error_code' => 'P-04');												
					}
		} else {
				$result=array('error'	   => true,
					  'error_msg'  => __('Please specify a valid Reading Group name.','wp-jv-post-reading-groups'),
					  'error_code' => 'P-05');								  
			}	    		
	}
	else $result=array('error'	   => true,
			 		   'error_msg'  => 'Something went wrong',
					   'error_code' => 'F-03');			
					   
	//to debug uncomment the following 4 lines	
	/*
	$result=array_merge($result,array('action'		=>	'rename',
					'RGToRename'	=>	$RGToRename,
					'NewRGName'		=>	$NewRGName
					));	
	*/
	header('Content-Type: application/json');
	die(json_encode($result));	
}
add_action('wp_ajax_wp_jv_prg_save_renamed_rg_to_db','wp_jv_prg_save_renamed_rg_to_db');


//Delete row (AJAX call handler)
function wp_jv_prg_delete_rg() {		
    //Check if we are getting hacked
	$url=parse_url($_POST['delurl']);
	parse_str($url['query'],$params);
	if (empty($params['action']) || ( empty($params['rg']) && $params['rg'] !=0 ) || empty($params['jv_prg_nonce']) || !wp_verify_nonce($params['jv_prg_nonce'],'delete'. $params['rg'])) {
		$result=array('error'=> true,
					  'error_msg'  => 'Something went wrong.',
					  'error_code' => 'F-04'
					);
		//to debug uncomment the following line
		//$result=array_merge($result,$params);
		header('Content-Type: application/json');
		die(json_encode($result));
	}
	
	//Remove this RG from all Posts where it is being used
	
	//Get list of posts might affected
	global $wpdb;
	$posts_affected = $wpdb->get_results("
	select id, meta_value
	from 	$wpdb->posts p,
			$wpdb->postmeta pm
	where p.id=pm.post_id
		  and pm.meta_key='wp_jv_post_rg'
	");	

	//Get all RGs
	$wp_jv_prg_rg_settings=get_option('wp_jv_prg_rg_settings');
	//Get the one we need to delete
	$RG_to_be_deleted=$params['rg']; //this is an RG ID actually


	//Go through list of posts might affected and remove this RG one by one if associated
	//$value->id == Post ID 
	//unserialize($value->meta_value) == array(RG ID)			
	foreach ($posts_affected as $value) {	
		//We don't care about those posts which has got no RG associated at all
		$postRG=unserialize($value->meta_value);
		if (!empty($postRG)) {
			//Search for RG we want to delete
			if (in_array($RG_to_be_deleted, $postRG)) {
				update_post_meta($value->id, 'wp_jv_post_rg', array_diff($postRG,array($RG_to_be_deleted)));
			}
		}			
	}
	
	//Get rid of that RG
	update_option('wp_jv_prg_rg_settings',array_diff($wp_jv_prg_rg_settings, array($wp_jv_prg_rg_settings[$RG_to_be_deleted])));			
	
	$result=array('error'=> false);
	
	//to debug uncomment the following line
	//$result=array_merge($result,$params);       
	
	header('Content-Type: application/json');
	die(json_encode($result));
	
}
add_action('wp_ajax_wp_jv_prg_delete_rg', 'wp_jv_prg_delete_rg');


//Adding settings to Settings->Reading
function wp_jv_prg_add_rg_to_settings_reading() {
	add_settings_section('wp_jv_prg_rg_settings','WP JV Post Reading Groups','wp_jv_prg_settings','reading');
	
	add_option('wp_jv_prg_rg_settings',array());
}
add_action( 'admin_init', 'wp_jv_prg_add_rg_to_settings_reading' );


//WP JV Post Reading Groups Settings section intro text
function wp_jv_prg_settings() {  	
	//Wrapper
	echo '<div class="jv-wrapper">';		
	
	//Header
	echo '<div class="jv-header">';
	echo __('Create your Reading Groups and then assign these to','wp-jv-post-reading-groups').' <a href="users.php">'.__('users','wp-jv-post-reading-groups').'</a>.<br><br>';	
	echo '</div>'; //jv-header end
	
	//Left side: Add new RG functionality
	echo '<div class="jv-left">';	
	echo __('Reading Group Name','wp-jv-post-reading-groups');
	echo '<br>';
    echo '<input type="text" name="new_reading_group" class="jv-new-reading-group-text" id="jv-new-reading-group-text"/><br>';				
	echo '<input type="button" id="btnAddNewRG" class="button-primary" value="'.__('Add New Reading Group','wp-jv-post-reading-groups').'" />';
	//Add loading image - hidden by default
	echo '<img id="spnAddRG" src="'. admin_url() . '/images/wpspin_light.gif" style="display: none;">';		
	echo '</div>';//jv-left end
	
    //Right side: List of reading groups	
	echo '<div class="jv-right">';		
	$wp_jv_prg_reading_groups_table = new WP_JV_PRG_List_Table();	
	$wp_jv_prg_reading_groups_table->prepare_items();	
	$wp_jv_prg_reading_groups_table->display(); 			
	echo '</div>'; //jv-right end

	//no footer this time			
	echo '<div class="jv-footer">';
	echo '</div>'; //jv-footer end
	
	echo '</div>'; //jv-wrapper end	
	
}

/************************************************************************************************************/
//Add Reading Groups to User's Profile screen
/************************************************************************************************************/

function wp_jv_prg_user_profile($user) {  	

	//Only admins can see these options
	if ( !current_user_can( 'edit_users' ) ) { return; }

	//Wrapper
	echo '<div class="jv-wrapper">';
		
	//Header
	echo '<div class="jv-header">';
	echo '<h3>WP JV Reading Groups</h3>';	
	echo '</div>'; //jv-header end
	
	echo '<div class="jv-content">';	
	
	if (!empty($user->ID)) {
		if ( user_can($user->ID, 'edit_users' ) ) { 
			echo __('Administrators access all posts.','wp-jv-post-reading-groups').'<br>'; 			
		}	
	}
			
	echo __('Grant permissions for the following Reading Group(s)','wp-jv-post-reading-groups').'<br>';
	
	//Get all available RGs from database
	$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
	
	$wp_jv_user_rg=null;			
	//Get current user's permissions				
	if (!empty($user->ID)) {
		$wp_jv_user_rg=get_user_meta($user->ID, 'wp_jv_user_rg',true);
	}
	
	//Echo checkboxes and tick saved selections	
	if (empty($wp_jv_prg_rg_settings)) {
		echo __('Create some groups first at','wp-jv-post-reading-groups');
		echo ' <a href="options-reading.php">';
		echo __('Settings -> Reading','wp-jv-post-reading-groups');
		echo '</a>';
	}
	else {
		foreach ($wp_jv_prg_rg_settings as $key => $value) {					
			echo '<input type="checkbox" name="wp-jv-reading-group-field-'. $key. '" value="'. $wp_jv_prg_rg_settings[$key]. '"';
			if (!empty($wp_jv_user_rg) && in_array($key, $wp_jv_user_rg,true)) { echo 'checked="checked"';} 
			echo '/>'. $wp_jv_prg_rg_settings[$key]. '<br>';
			}
		}			
	
	echo '</div>'; //jv-content end

	//no footer this time			
	echo '<div class="jv-footer">';
	echo '</div>'; //jv-footer end
	
	echo '</div>'; //jv-wrapper end	
	
}
add_action( 'show_user_profile', 'wp_jv_prg_user_profile' );
add_action( 'edit_user_profile', 'wp_jv_prg_user_profile' );


//Save Profile settings
function wp_jv_prg_save_user_profile( $user_id ) {
	//Only admins can save
	if ( !current_user_can( 'edit_users', $user_id ) ) { return; }
	
	$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
		
	if (empty($wp_jv_prg_rg_settings)) { 
		return; 
	} 
	else {
		$newRG=null;
		foreach ($wp_jv_prg_rg_settings as $key => $value) {	
			if (isset($_POST['wp-jv-reading-group-field-'. $key])) {
				$newRG[]=$key;
			}
		}
		update_user_meta( $user_id, 'wp_jv_user_rg', $newRG );
		//Check if new RG saved successfully
		if ( get_user_meta($user_id,  'wp_jv_user_rg', true ) != $newRG ) {	wp_die('Something went wrong.<br>[Error: F-05] ');}		
	}
}
add_action( 'personal_options_update', 'wp_jv_prg_save_user_profile' );
add_action( 'edit_user_profile_update', 'wp_jv_prg_save_user_profile' );


/************************************************************************************************************/
/* Add Reading Groups to Add New User screen */
/************************************************************************************************************/
add_action('user_new_form','wp_jv_prg_user_profile');
add_action('user_register','wp_jv_prg_save_user_profile');

/************************************************************************************************************/
/* Add Reading Groups to All Users screen */
/************************************************************************************************************/

//Add column
function wp_jv_prg_all_users_column_register( $columns ) {
    $columns['wp_jv_prg'] = __('Reading Groups','wp-jv-post-reading-groups');
    return $columns;
}

//Add rows
function wp_jv_prg_all_users_column_rows( $empty, $column_name, $user_id ) {
    $rg=null;
	if ( 'wp_jv_prg' != $column_name ) {
        return $empty;
	}
	if (user_can($user_id,'edit_users')) {$rg=__('Access all RGs','wp-jv-post-reading-groups');}
	else {		
		$wp_jv_user_rg=get_user_meta($user_id,'wp_jv_user_rg',true);
		//#TODO: add number of posts per RG
		if (empty($wp_jv_user_rg)) {$rg = null;} //Access only public posts
		else {
			$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
			foreach ($wp_jv_user_rg as $key => $value) {
				$rg=$rg. $wp_jv_prg_rg_settings[$value]. '<br>';
			}	
		}
	}
    return $rg;	
}
add_filter( 'manage_users_columns', 'wp_jv_prg_all_users_column_register' );
add_filter( 'manage_users_custom_column', 'wp_jv_prg_all_users_column_rows', 10, 3 );

/************************************************************************************************************/
//Add Reading Groups to All Posts screen
/************************************************************************************************************/

//Add column
function wp_jv_prg_all_posts_column_register( $columns ) {
    $columns['wp_jv_prg'] = __('Reading Groups','wp-jv-post-reading-groups');
    return $columns;
}

//Add rows
function wp_jv_prg_all_posts_column_rows($column_name, $post_id ) {
    if ( 'wp_jv_prg' != $column_name ) {
        return;
	}
	$wp_jv_post_rg=get_post_meta($post_id,'wp_jv_post_rg',true);	
	$rg = null;
	if (empty($wp_jv_post_rg)) {$rg = null;} //Access only public posts
	else {
		$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
		foreach ($wp_jv_post_rg as $key => $value) {
			$rg=$rg. $wp_jv_prg_rg_settings[$value]. '<br>';
		}	
	}	
    echo $rg;	
}
add_filter( 'manage_posts_columns', 'wp_jv_prg_all_posts_column_register' );
add_filter( 'manage_posts_custom_column', 'wp_jv_prg_all_posts_column_rows', 10, 2 );


/************************************************************************************************************/
//Influence display posts 
/************************************************************************************************************/


//Display private posts as well (only those for which user has permissions)
function wp_jv_prg_posts_where_statement($where) {	
	global $wpdb;			
	
	if (is_page()) {return $where;}
	if (is_feed()) {return $where;}
	if (is_attachment()) {return $where;}
	if (is_preview()) {return $where;}
	
	if (is_admin()){			
		return $where;
	}
    
	if(is_user_logged_in()) {		
		$who_is_the_user=get_current_user_id(); 
		// Handle categories, tags
		if (is_archive()) {	
			if (is_category()) {
				$category=single_cat_title(null,false); 
				$all_posts_in_category = get_posts(array('category_name' => $category,'post_status' =>'any','posts_per_page'=>-1));
				
				$to_show=array();
				foreach ($all_posts_in_category as $key => $value) {								
					if ((wp_jv_prg_user_can_see_a_post(get_current_user_id(), $value->ID))) {
						$to_show[]=$value->ID;				
					}	
				}
				if (!empty($to_show)) {
					$where =" AND $wpdb->posts.post_type = 'post' AND $wpdb->posts.ID IN (". implode(',',$to_show). ")";
				}
				return $where;
			}
			else if (is_tag()) {				
				//#TODO: finish up tags
				return $where;			
			}
			else {return $where;}			
		}	
		//Display all to admins
		if (user_can($who_is_the_user,'edit_users')) {						
			if (is_single()) {
				$where .= " AND $wpdb->posts.post_status IN ('private','publish')";
			}
			else {					
				$where = " AND $wpdb->posts.post_type = 'post' 
						   AND $wpdb->posts.post_status IN ('private','publish') ";				
			}			
			return $where;
		}
		else {			
		//sigle post
		if (is_single()) {					
			$where .= " AND $wpdb->posts.post_status IN ('private','publish')";
		}
		//multiple posts
		else {							
			$request = "						
			SELECT ID, 
				   meta_value as wp_jv_post_rg				   
			FROM $wpdb->posts, 
				 $wpdb->postmeta
			WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
			AND post_status='private' AND meta_key='wp_jv_post_rg'";		
			$all_posts = $wpdb->get_results($request);
			$to_show=array();
			foreach ($all_posts as $key => $value) {
				if ((wp_jv_prg_user_can_see_a_post(get_current_user_id(), $value->ID))) {
					$to_show[]=$value->ID;				
				}	
				//If user has access to at least one private post
				
				if (!empty($to_show)) {					
					$where = " AND $wpdb->posts.post_type = 'post' 
							   AND ( ($wpdb->posts.ID IN (".implode(',',$to_show).") AND $wpdb->posts.post_status ='private') 
									 OR ($wpdb->posts.post_author=$who_is_the_user AND $wpdb->posts.post_status ='publish') 
									 OR  $wpdb->posts.post_status ='publish') ";
					
				}					
			}
		} //End multiple post
		} //End non-admins
	} //End change only for logged-in users		
	return $where;
}
add_filter( 'posts_where' , 'wp_jv_prg_posts_where_statement' );


//Enable private post URLs to eligible users
function wp_jv_prg_posts_results($posts) {	
	if (is_admin()){return $posts;}

	if (is_archive()) {		
		//fixing categories
		if (is_category() && !empty($posts)) {
			$category=single_cat_title(null,false); 			
			foreach ($posts as $value) {
				if (in_category($category,$value->ID))	{
					$value->post_status = 'publish';						
				}
			}
		}
		else return $posts;
	}
	else {
		//for posts only
		if(is_user_logged_in() && !empty($posts)) {		
			foreach ($posts as $value) {						
				if (wp_jv_prg_user_can_see_a_post(get_current_user_id(), $value->ID))	{	
					$value->post_status = 'publish';					
					}
			}
		}	
	}
	return $posts;
}  
add_filter('posts_results', 'wp_jv_prg_posts_results');

/************************************************************************************************************/
// Influence how comments are displayed
//
// Show comments for private posts if the user is eligible
/************************************************************************************************************/

add_filter( 'widget_comments_args', 'wp_jv_prg_show_private_comments' );
function wp_jv_prg_show_private_comments( $comment_args ) {
	global $wpdb;

	if (is_admin()){			
		return $comment_args;
	}
	$comment_args['status'] = 'approve';
	if(is_user_logged_in()) {		
		$who_is_the_user=get_current_user_id(); 
		//Find out which private posts the user can read
		$private_posts = "						
		SELECT ID, 
			   meta_value as wp_jv_post_rg			   
		FROM $wpdb->posts, 
			 $wpdb->postmeta
		WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
		AND post_status='private' AND meta_key='wp_jv_post_rg'";		
		$all_private_posts = $wpdb->get_results($private_posts);
		$private_to_show=array();
		foreach ($all_private_posts as $key => $value) {
			if ((wp_jv_prg_user_can_see_a_post($who_is_the_user, $value->ID))) {
				$private_to_show[]=$value->ID;				
			}	
		}
		
		if (!empty($private_to_show)) { 
			//If user can access any private we need to find out which are the public ones
			$public_posts = "						
			SELECT ID				   
			FROM $wpdb->posts 
			WHERE post_status='publish'";		
			$all_public_posts  = $wpdb->get_results($public_posts );
			$to_show=$private_to_show; 
			foreach ($all_public_posts  as $key => $value) {
				if ((wp_jv_prg_user_can_see_a_post($who_is_the_user, $value->ID))) {
					$to_show[]=$value->ID;				
				}	
			}
			// we need to list all posts IDs even if those are public
			$comment_args['post__in'] = $to_show; 
			$comment_args['post_status'] = array('publish','private');
		}
		else $comment_args['post_status'] = array('publish');
	}
	return $comment_args;
}		

//add support for WP JV Custom Email Settings Plugin
function wp_jv_prg_user_can_see_a_post($user_id, $post_id) {	
	$user_can_see_a_post=false;
	//If a post is public then anybody can see it
	if (get_post_status($post_id)== 'publish') {$user_can_see_a_post=true;}
	else {
		//Display all to admins
		if (user_can($user_id,'edit_users')) {$user_can_see_a_post=true;}
		else {
			//Get current user and his/her permissions
			$user_permissions = get_user_meta($user_id,'wp_jv_user_rg',true);
			if (!is_array($user_permissions)) {
				$user_permissions=str_split($user_permissions);
			}
			//if current user has any kind of permission...				
			if ($user_permissions) {	
				$post_permitted=get_post($post_id)->wp_jv_post_rg;
				//Convert to array if necessary
				if (!is_array($post_permitted)) { 
					$post_permitted=str_split($post_permitted);
				}							
				//If post permissions set AND current user has appropriate permissions add this post to the list of posts to show				
				$this_user_can_see_this_post=array_intersect($user_permissions, $post_permitted);
				if (!empty($user_permissions) && !empty($this_user_can_see_this_post)) {	
					$user_can_see_a_post=true;
				}
			}		
		}
	}
	return $user_can_see_a_post;
}


//Remove 'Private:' text from title
function wp_jv_prg_remove_private_from_title($title){
	return str_replace( sprintf( __('Private: %s'), '' ), '', $title );
}
add_filter('the_title', 'wp_jv_prg_remove_private_from_title');



/************************************************************************************************************/ 
/* Adds Donate link to Plugin page next under Plugin description */
/************************************************************************************************************/ 

function wp_jv_prg_donate_link($links, $file) {
	if ( strpos( $file, 'wp-jv-post-reading-groups.php' ) !== false ) {
	$new_links = array(
						'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JNF92QJY4PGGA&lc=HU&item_name=WP%20JV%20Post%20Reading%20Groups%20%2d%20Plugin%20Donation&item_number=1&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" target="_blank">Donate</a>'
					  );
	
	$links = array_merge( $links, $new_links );
	}
return $links;
}
add_filter( 'plugin_row_meta', 'wp_jv_prg_donate_link' , 10, 2 );

?>