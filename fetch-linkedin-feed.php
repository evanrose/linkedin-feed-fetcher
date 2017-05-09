<?php
//Plugin Name: Import Linkedin Jobs Feed
/*
Plugin URI: http://github.com/evanrose
Description: Import Linkedin Jobs Feed
Author: Evan Rose
Version: 2.0
Author URI: evan@evanrose.com
*/

defined( 'ABSPATH' ) or die();
date_default_timezone_set('America/New_York');

/**
@ Set up chron job on activation
*/

/* Uncomment to test
function my_cron_schedules($schedules){
	if(!isset($schedules["1min"])){
        $schedules["5min"] = array(
            'interval' => 60,
            'display' => __('Once every 5 minutes'));
    }
    if(!isset($schedules["5min"])){
        $schedules["5min"] = array(
            'interval' => 5*60,
            'display' => __('Once every 5 minutes'));
    }
    if(!isset($schedules["30min"])){
        $schedules["30min"] = array(
            'interval' => 30*60,
            'display' => __('Once every 30 minutes'));
    }
    return $schedules;
}

add_filter('cron_schedules','my_cron_schedules');
wp_schedule_event( time(), '5min', 'mcn_li_daily_event' );	

*/

register_activation_hook( __FILE__, 'mcn_li_activation' );
function mcn_li_activation() {

	wp_schedule_event( time(), 'daily', 'mcn_li_daily_event' );	
	//wp_schedule_event( time(), '1min', 'mcn_li_daily_event' );	
}
add_action( 'mcn_li_daily_event', 'mcn_li_create_job_post' );

/**
@ Set up values for use by the functions as well as the options page files
*/

$li_args = array();
$li_args = array(

    'li_client_id'		=> get_option( 'mcn_li_creds_client_id' ),
    'li_api_secret'		=> get_option( 'mcn_li_creds_api_secret' ),
    'li_company_id'		=> get_option( 'mcn_li_creds_company_id' ),
    'li_state_val'		=> get_option( 'mcn_li_state_value' ),
    'li_oauth_token'	=> get_option( 'mcn_li_oauth_token' ),
    'li_token_expires'	=> get_option( 'mcn_li_updated_timestamp' ),
    'li_redirect_uri'	=> 'http://' . $_SERVER['SERVER_NAME'] . '/wp-content/plugins/fetch-linkedin-feed/generate-token.php',
);

/**
@ Include options pages 
*/

include( 'options-pages-credentials.php' );
include( 'options-pages-oauth.php' );

/**
@ MAIN FUNCTION!
*/

//function is_user_logged_in() {}
//mcn_li_create_job_post();
function mcn_li_create_job_post() {

	global $wpdb;
	global $li_args;

	$meta_key	= 'li_job_id';
	$post_type 	= 'li_job';

	$response = feed_fetcher( $li_args );
	//include( 'feed_array.php' ); //For testing purposes

	//var_export( $response );

	if ( $response ) {

		// Get the Linkedin ID's from wp_postmeta and their post ID's as key
		$li_ids_in_db = get_li_db_ids( $meta_key, $post_type );
		//print_r( $li_ids_in_db );
		//exit;

		// Create array of Linkedin ID's in the feed so we know we can find out which is new
		foreach( $response['values'] as $li_job ) {

        	if ( isset( $li_job['updateContent']['companyJobUpdate'] ) ) {

				$li_ids_in_feed[] = $li_job['updateContent']['companyJobUpdate']['job']['id'];
			}
	    }

	    // Returns ID's in feed that aren't in the DB, so create new posts; not in use except to delete posts

		$new_linkedin_ids 	= array_diff( $li_ids_in_feed, $li_ids_in_db );
		//print_r( $new_linkedin_ids );
		//exit;
		

		// Returns list of posts whose li_id's are in DB but no longer in feed for deletion
		$old_li_posts = array_diff( $li_ids_in_db, $li_ids_in_feed );
		//print_r( $old_li_posts );
		//exit;

		if ( ! empty( $old_li_posts ) ) {

			foreach ( $old_li_posts as $post_id => $linkedin_id ) {

				//echo $post_id;
				$wpdb->delete( 'wp_posts', array( 'ID' => $post_id ), '%d' );
			}
		}

		//exit;

	    //foreach( $response['values'] as $li_job ) {
		foreach( $response['values'] as $li_job ) {

        	if ( isset( $li_job['updateContent']['companyJobUpdate']['job'] ) ) {

				$li_job_meta 	= $li_job['updateContent']['companyJobUpdate']['job'];
				
		    	$li_location	= $li_job_meta['locationDescription'];
		        $li_url    		= $li_job_meta['siteJobRequest']['url'];
		        $meta_id		= $li_job_meta['id'];
				$post_content 	= trim( str_replace( array( 'Summary', '&nbsp;' ), '', $li_job_meta['description'] ) );
		        $post_title 	= $li_job_meta['position']['title'];
		        $post_date_gmt	= date( 'Y-m-d H:i:s', $li_job['timestamp']/1000 );
				
		        $args = array();
				$args = array(
						
					'meta_query' 	=> array(
				
						array(
							'key'   => $meta_key,
							'value' => $meta_id,
						)
					),
					'post_type'		=> $post_type,
				);

				// Is the linkedin ID in the postmeta table? If not...
				$post_meta = get_posts( $args );

				//print_r( $post_meta );

				if ( empty( $post_meta ) ) {

					$post = array();
					$post = array(

						'post_author'	=> 1,
						'post_content'	=> $post_content,
						'post_date_gmt'	=> $post_date_gmt,
						'post_name'		=> sanitize_title( $post_title ), 
						'post_status'   => 'publish',
						'post_title'    => $post_title, 
						'post_type'		=> $post_type,
					);

					$post_id = wp_insert_post( $post );
				
					add_post_meta( $post_id, $meta_key, $meta_id );
					add_post_meta( $post_id, 'li_location', $li_location );
					add_post_meta( $post_id, 'li_url', $li_url );
				}
			}
	    }
	}
}

/** 
@ Get meta values for li_id 
*/

function get_li_db_ids( $meta_key,  $post_type ) {

    $posts = get_posts(
        array(
            'post_type' => $post_type,
            'meta_key' => $meta_key,
            'posts_per_page' => 20,
			'order' => 'ASC',        
		)
    );

    $meta_values = array();
    foreach( $posts as $post ) {
        $meta_values[$post->ID] = get_post_meta( $post->ID, $meta_key, true );
    }

    return $meta_values;
}

/** 
@ Fetch the feed
*/

function feed_fetcher( $li_args ) {

	// Set up values for use by the feed fetcher
	$li_client_id   = $li_args['li_client_id'];
	$li_api_secret  = $li_args['li_api_secret'];
	$li_company_id  = $li_args['li_company_id'];
	$li_path        = '/v1/companies/' . $li_company_id . '/updates';
	$li_oauth_token = $li_args['li_oauth_token'];

	// Token expires in 60 days so start sending warnings daily	 after 55 

	// get new value from oauth page

	$expiring 	= get_option( 'mcn_li_updated_timestamp' ) + DAY_IN_SECONDS * 55;
	$expired	= $expiring + DAY_IN_SECONDS * 5;

	if ( $expiring < time() ) {

		echo 'expiring';
	}

	if ( $expired < time() ) {
		
		echo 'expired';
	}
	else {

		require_once 'vendor/autoload.php';

		$client = new \Splash\LinkedIn\Client( $li_client_id, $li_api_secret );
		$client->setAccessToken( $li_oauth_token );
		return $response = $client->fetch( $li_path );
	}
}

/**
/* Deactivate chron on plugin deactivation
*/
register_deactivation_hook(__FILE__, 'mcn_li_deactivation' );

function mcn_li_deactivation() {

	wp_clear_scheduled_hook( 'mcn_li_daily_event' );
}