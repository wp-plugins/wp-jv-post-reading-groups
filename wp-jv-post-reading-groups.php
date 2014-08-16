<?php
/**
 * Plugin Name: WP JV Post Reading Groups
 * Plugin URI: http://janosver.com/projects/wordpress/wp-jv-post-reading-groups
 * Description: Grant read-only permission for selected users (with no administrator role) on selected private posts 
 * Version: 1.0
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
function AddRGMetaBoxHead() {
	add_meta_box('wp_jv_prg_sectionid',__( 'WP JV Reading Groups', 'wp_jv_prg_textdomain' ),'AddRGMetaBox',	'post');
}
add_action( 'add_meta_boxes', 'AddRGMetaBoxHead' );

//Prints the box content
function AddRGMetaBox( $post ) {

	// Add an nonce field so we can check for it later
	wp_nonce_field( 'wp_jv_prg_meta_box', 'wp_jv_prg_meta_box_nonce' );

	//Get all available RGs from database
	$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
	//Get current user's permissions
	$wp_jv_post_rg=get_post_meta($post->ID, 'wp_jv_post_rg',true);
	
	//Echo checkboxes and tick saved selections	
	if (empty($wp_jv_prg_rg_settings)) {_e('Create some groups first at <a href="options-reading.php">Settings -> Reading</a>','wp_jv_prg_textdomain');} 
	else {
	     _e( 'Select who can read this post<br>', 'wp_jv_prg_textdomain');
		 
		foreach ($wp_jv_prg_rg_settings as $key => $value) {	
			echo '<input type="checkbox" name="wp-jv-reading-group-field-'. $key. '" value="'. $wp_jv_prg_rg_settings[$key]. '"';
			if (!empty($wp_jv_post_rg) && in_array($key, $wp_jv_post_rg,true)) { echo 'checked="checked"';} 
			echo '/>'. $wp_jv_prg_rg_settings[$key]. '<br>';
			}
		}				
}

//When the post is saved, saves our custom data
function SaveRGMetaBox( $post_id ) {

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
	
	$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
	if (!empty($wp_jv_prg_rg_settings)) {	
		foreach ($wp_jv_prg_rg_settings as $key => $value) {	
			if (isset($_POST['wp-jv-reading-group-field-'. $key])) {$NewRG[]=$key;}
			}	
	}
	// Update reading groups custom field in the database.
	update_post_meta( $post_id, 'wp_jv_post_rg', $NewRG );	
}
add_action( 'save_post', 'SaveRGMetaBox' );


/************************************************************************************************************/
/* Creating Reading Groups @ Settings-> Reading */
/************************************************************************************************************/

//Load WP_List_Table if not loaded
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/*Start class WP_JV_PRG_List_Table*/
class WP_JV_PRG_List_Table extends WP_List_Table {

	function __construct(){
		global $status, $page;
		parent::__construct( array(
			'singular'  => __( 'Reading Group' ),     //singular name of the listed records
			'plural'    => __( 'Reading Groups' ),   //plural name of the listed records
			'ajax'      => true        				
			));			
	}
	
	function get_columns(){
		$columns = array('reading_group' => 'Reading Group');
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
		
	function display_tablenav(){
		//Leave it empty to remove tablenav
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
			'edit'		=> sprintf('<a class="lnkEdit" data-RG="'. $ItemKey. '" href="'. wp_nonce_url( admin_url('options-reading.php?action=edit&rg='. $ItemKey),'edit'. $ItemKey,'jv_nonce'). '">Rename</a>'),				
			//Delete link
			'delete'		=> sprintf('<a class="lnkDelete" href="'. wp_nonce_url( admin_url('options-reading.php?action=delete&rg='. $ItemKey),'delete'. $ItemKey,'jv_nonce'). '">Delete</a>')				
			);				
		return sprintf('%1$s %2$s %3$s', $renamediv, $itemdiv, $this->row_actions( $actions ));			
	}
		
} 
/*End class WP_JV_PRG_List_Table*/


//Initialize js methods
function LoadJSMethods() {
   wp_register_script( 'wp_jv_prg_script', plugin_dir_url(__FILE__).'wp-jv-post-reading-groups.js', array('jquery') );
   wp_register_style( 'wp_jv_rg_styles',plugin_dir_url(__FILE__).'wp-jv-post-reading-groups.css');
   //Make sure we can use jQuery
   wp_enqueue_script( 'jquery' );   
    
   //Load script
   wp_enqueue_script( 'wp_jv_prg_script' );
   //Load style
   wp_enqueue_style('wp_jv_rg_styles');
   //Improve security
   $nonce_array = array( 'wp_jv_rg_nonce' =>  wp_create_nonce ('wp_jv_rg_nonce') );
   wp_localize_script( 'wp_jv_prg_script', 'wp_jv_obj', $nonce_array );
}
add_action( 'init', 'LoadJSMethods' );


//Refresh WP-List-Table (AJAX call handler)
function RefreshRGList() {	
	$wp_jv_prg_reading_groups_table = new WP_JV_PRG_List_Table();	
	$wp_jv_prg_reading_groups_table->ajax_response();		
}
add_action('wp_ajax_RefreshRGList', 'RefreshRGList');


//Add new Reading Group to database (AJAX call handler)
function AddNewRGtoDB() {		
    
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
									'error_msg'  => 'Reading Group name "'. $newRG. '"already exists.',
									'error_code' => 'P-01');			
				
					}
		} else {
				$result=array('error'	   => true,
					  'error_msg' => 'Please specify a valid Reading Group name.',
					  'error_code' => 'P-02');			
			}	    		
	}
	else $result=array('error'	    => true,
			 		   'error_msg'  => 'Something went wrong',
					   'error_code' => 'F-02');			
	//to debug uncomment the following 3 lines	
	$result=array_merge($result,array('action'		=>	'add',
					'newRG'	=>	sanitize_text_field($_POST['newrg'])
					));	
	header('Content-Type: application/json');
	die(json_encode($result));	
}
add_action('wp_ajax_AddNewRGtoDB','AddNewRGtoDB');



//Rename existing Reading Group in database (AJAX call handler)
function SaveRenamedRGtoDB() {		
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
										'error_msg'  => 'Reading Group "'. $NewRGName. '" already exists.',
										'error_code' => 'P-03');												
					
						}
				}	
				else {
						$result=array('error'	   => true,
									'error_msg'  => 'Reading Group "'. $data[$RGToRename]. '" does not  exists.',
									'error_code' => 'P-04');												
					}
		} else {
				$result=array('error'	   => true,
					  'error_msg'  => 'Please specify a valid Reading Group name.',
					  'error_code' => 'P-05');								  
			}	    		
	}
	else $result=array('error'	   => true,
			 		   'error_msg'  => 'Something went wrong',
					   'error_code' => 'F-03');			
					   
	//to debug uncomment the following 4 lines	
	//$result=array_merge($result,array('action'		=>	'rename',
	//				'RGToRename'	=>	$RGToRename,
	//				'NewRGName'		=>	$NewRGName
	//				));	
	
	header('Content-Type: application/json');
	die(json_encode($result));	
}
add_action('wp_ajax_SaveRenamedRGtoDB','SaveRenamedRGtoDB');


//Delete row (AJAX call handler)
function DeleteRG() {		
    //Check if we are getting hacked
	$url=parse_url($_POST['delurl']);
	parse_str($url['query'],$params);
	if (empty($params['action']) || ( empty($params['rg']) && $params['rg'] !=0 ) || empty($params['jv_nonce']) || !wp_verify_nonce($params['jv_nonce'],'delete'. $params['rg'])) {
		$result=array('error'=> true,
					  'error_msg'  => 'Something went wrong.',
					  'error_code' => 'F-04'
					);
		//to debug uncomment the following line
		$result=array_merge($result,$params);
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
add_action('wp_ajax_DeleteRG', 'DeleteRG');


//Adding settings to Settings->Reading
function AddRGtoSettingsReading() {
	add_settings_section('wp_jv_prg_rg_settings','WP JV Post Reading Groups','PRGSettings','reading');
	
	add_option('wp_jv_prg_rg_settings',array());
}
add_action( 'admin_init', 'AddRGtoSettingsReading' );


//WP JV Post Reading Groups Settings section intro text
function PRGSettings() {  	
	//Wrapper
	echo '<div class="jv-wrapper">';		
	
	//Header
	echo '<div class="jv-header">';
	echo 'Create your Reading Groups and then assign these to <a href="users.php">users</a>.<br><br>';	
	echo '</div>'; //jv-header end
	
	//Left side: Add new RG functionality
	echo '<div class="jv-left">';	
	echo 'Reading Group Name<br>';
    echo '<input type="text" name="new_reading_group" class="jv-new-reading-group-text" id="jv-new-reading-group-text"/><br>';				
	echo '<input type="button" id="btnAddNewRG" class="button-primary" value="Add New Reading Group" />';
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

function RGUserProfile($user) {  	

	//Only admins can see these options
	if ( !current_user_can( 'edit_user' ) ) { return; }

	//Wrapper
	echo '<div class="jv-wrapper">';
		
	//Header
	echo '<div class="jv-header">';
	echo '<h3>WP JV Reading Groups</h3>';	
	echo '</div>'; //jv-header end
	
	if ( !current_user_can( 'edit_user' ) ) { return; }
	
	echo '<div class="jv-content">';	
	
	if ( user_can($user->id, 'edit_user' ) ) { 
		echo 'Administrators access all posts.<br>'; 
		
	}
	else {
	
	echo 'Grant permissions for the following Reading Group(s)<br>';
	
	//Get all available RGs from database
	$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
	//Get current user's permissions
	$wp_jv_user_rg=get_user_meta($user->ID, 'wp_jv_user_rg',true);

	//Echo checkboxes and tick saved selections	
	if (empty($wp_jv_prg_rg_settings)) {_e('Create some groups first at <a href="options-reading.php">Settings -> Reading</a>','wp_jv_prg_textdomain');} 
	else {
		foreach ($wp_jv_prg_rg_settings as $key => $value) {	
			echo '<input type="checkbox" name="wp-jv-reading-group-field-'. $key. '" value="'. $wp_jv_prg_rg_settings[$key]. '"';
			if (!empty($wp_jv_user_rg) && in_array($key, $wp_jv_user_rg,true)) { echo 'checked="checked"';} 
			echo '/>'. $wp_jv_prg_rg_settings[$key]. '<br>';
			}
		}	
	}
	echo '</div>'; //jv-content end

	//no footer this time			
	echo '<div class="jv-footer">';
	echo '</div>'; //jv-footer end
	
	echo '</div>'; //jv-wrapper end	
	
}
add_action( 'show_user_profile', 'RGUserProfile' );
add_action( 'edit_user_profile', 'RGUserProfile' );


//Save Profile settings
function SaveRGUserProfile( $user_id ) {
	//Only admins can save
	if ( !current_user_can( 'edit_user', $user_id ) ) { return; }
	$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
	
	if (empty($wp_jv_prg_rg_settings)) {return;} 
	else {
		foreach ($wp_jv_prg_rg_settings as $key => $value) {	
			if (isset($_POST['wp-jv-reading-group-field-'. $key])) {$newRG[]=$key;}
			}
	}
	
	update_user_meta( $user_id, 'wp_jv_user_rg', $newRG );
	
	//Check if new RG saved successfully
	if ( get_user_meta($user_id,  'wp_jv_user_rg', true ) != $newRG ) {	wp_die('Something went wrong.<br>[Error: F-05] ');}
	
}
add_action( 'personal_options_update', 'SaveRGUserProfile' );
add_action( 'edit_user_profile_update', 'SaveRGUserProfile' );


/************************************************************************************************************/
/* Add Reading Groups to Add New User screen */
/************************************************************************************************************/
add_action('user_new_form','RGUserProfile');
add_action('user_register','SaveRGUserProfile');

/************************************************************************************************************/
/* Add Reading Groups to All Users screen */
/************************************************************************************************************/

//Add column
function AllUsersColumnRegisterRG( $columns ) {
    $columns['wp_jv_prg'] = 'Reading Groups';
    return $columns;
}

//Add rows
function AllUsersColumnRGRows( $empty, $column_name, $user_id ) {
    if ( 'wp_jv_prg' != $column_name ) {
        return $empty;
	}
	if (user_can($user_id,'edit_user')) {$rg='Access all RGs';}
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
add_filter( 'manage_users_columns', 'AllUsersColumnRegisterRG' );
add_filter( 'manage_users_custom_column', 'AllUsersColumnRGRows', 10, 3 );

/************************************************************************************************************/
//Add Reading Groups to All Posts screen
/************************************************************************************************************/

//Add column
function AllPostsColumnRegisterRG( $columns ) {
    $columns['wp_jv_prg'] = 'Reading Groups';
    return $columns;
}

//Add rows
function AllPostsColumnRGRows($column_name , $post_id ) {
    if ( 'wp_jv_prg' != $column_name ) {
        return;
	}
	$wp_jv_post_rg=get_post_meta($post_id,'wp_jv_post_rg',true);	
	if (empty($wp_jv_post_rg)) {$rg = null;} //Access only public posts
	else {
		$wp_jv_prg_rg_settings = get_option('wp_jv_prg_rg_settings');
		foreach ($wp_jv_post_rg as $key => $value) {
			$rg=$rg. $wp_jv_prg_rg_settings[$value]. '<br>';
		}	
	}	
    echo $rg;	
}
add_filter( 'manage_posts_columns', 'AllPostsColumnRegisterRG' );
add_filter( 'manage_posts_custom_column', 'AllPostsColumnRGRows', 10, 2 );


/************************************************************************************************************/
//Influence display posts 
/************************************************************************************************************/

//Display private posts as well (only those for which user has permissions)
function DisplayPrivatePostsIfAuthorized( $query ) { 
//
  if (!is_admin() && $query->is_main_query() &&  !current_user_can( 'edit_user' ) ){	  
    if(is_user_logged_in()){														
		global $wpdb;															
		$request = "						
			SELECT ID, 
				   meta_value as wp_jv_post_rg, 
				   true as limited
			FROM $wpdb->posts, 
				 $wpdb->postmeta
			WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
			AND post_status='private' AND meta_key='wp_jv_post_rg'
			union
			select id, null, false
			from $wpdb->posts
			where post_status='publish'
			";		
		$all_posts = $wpdb->get_results($wpdb->prepare($request,null));
		$to_show=array();
		//Get current user and his/her permissions
		$current_user_permissions = get_user_meta(get_current_user_id(),'wp_jv_user_rg',true);				
		
		foreach ($all_posts as $key => $value) {								
			//Add public posts to the list of posts to show
			$tags[] = wp_get_post_tags($value->ID); 
			if (!$value->limited) { 				
				$to_show[]=$value->ID;
			}
			else {
				$post_permitted=unserialize($value->wp_jv_post_rg);
				//If post permissions set AND current user has appropriate permissions add this post to the list of posts to show
				$this_user_can_see_this_post=array_intersect($current_user_permissions, $post_permitted);
				if (!empty($current_user_permissions) && !empty($this_user_can_see_this_post)) {				
						$to_show[]=$value->ID;
				}	
			}
		}	
		$query->set('post_status',array('publish','private'));
		$query->set('post__in',$to_show);
    }
  }
}
add_action( 'pre_get_posts', 'DisplayPrivatePostsIfAuthorized' );

//Remove 'Private:' text from title
function RemovePrivateFromTitle($title){
	return preg_replace('/Private:/', '', $title);
}
add_filter('the_title', 'RemovePrivateFromTitle');


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