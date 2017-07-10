<?php 
/*
Plugin Name: Spotify-Widget
Plugin URI: 
Description: Easily embed a peronalised Spotify widget on your Wordpress site. 
Author: James Kingsbury
Version: 1.0
Author URI: 
*/

	//setup shortcode so user can easily embed widget in wordpress installatin
	function sw_shortcode_init(){
		function sw_shortcode(){
			$plugin_url = plugin_dir_url(__FILE__);

			$auth_row = sw_get_latest_tokens();
			if($auth_row==false){
				return '
				<div class="sw_wrapper">
				<div class="spotify-widget">
					<h3>Please activate your widget in the Admin Panel</h3>
				</div>
				</div>';

			}
			$access_token = sw_get_useable_auth($auth_row->refresh_token);
			$json_track = sw_get_random_recent_track($access_token);
			$json_user = sw_get_current_user_details($access_token);


			return '
			<div class="sw_wrapper">
			<div class="spotify-widget">
				<div class="sw_user_pic_wrapper">
					<img class="sw_user_pic" src="' . $json_user['images'][0]['url'] . '">
				</div>
				<div class="sw_user_info">
					<h4 class="sw_display_name">' . $json_user['display_name'] . '</h4>
					<h5 class="sw_username">@' . $json_user['id'] . '</h5>
				</div>

				<div class="sw_track_label_wrapper">
					<h6 class="sw_track_label">Recently Listened To</h6>
				</div>
				<hr>
				<div class="sw_track_cover">
					<img src="' . $json_track['items'][0]['track']['album']['images'][0]['url'] . '">
				</div>
				<div class="sw_track_info">
					<h4 class="sw_track_name">' . $json_track['items'][0]['track']['name'] . '</h4>
					<h5 class="sw_track_artist">'. $json_track['items'][0]['track']['artists'][0]['name'] .'</h5>
				</div>
			</div>
			</div>';
		}
		add_shortcode('spotify', 'sw_shortcode');
	}

	//add submenu for spotify widget options 
	function sw_options_page(){
		add_submenu_page('options-general.php', 'Spotify Widget Options', 'Spotify Widget', 'manage_options', 'sw', 'sw_options_page_html');
	}

	//html for sw options page
	function sw_options_page_html(){
		echo '<div class="wrap">';
		echo '<h1 class="sw_title">' . esc_html(get_admin_page_title()) . '</h1>';
			
		//check if auth code or refresh code is stored  
		$auth_row = sw_get_latest_tokens();
		$redirect_uri = admin_url('options-general.php?page=sw');
		$url = "https://accounts.spotify.com/authorize/?client_id=5eb75a94bd9d4762b7fb97cc2f262472&response_type=code&redirect_uri=$redirect_uri&scope=user-read-recently-played";

		if($auth_row != false){
			$json_user = sw_get_current_user_details(sw_get_useable_auth($auth_row->refresh_token));
			echo '<div class="sw_account_wrapper">
			<img src="' . $json_user['images'][0]['url'] . '">
			<p>Logged in as @' . $json_user['id'] . '</p>
			</div></div>';

		}elseif(isset($_GET['code'])){
			$body = array('grant_type' => 'authorization_code', 'code' => $_GET['code'], 'redirect_uri' => $redirect_uri);
			$headers = array('Authorization' => 'Basic ' . base64_encode('5eb75a94bd9d4762b7fb97cc2f262472:8e7f14fc94df4f89a460d34ee12b6fbd'));
			$args = array('headers' => $headers, 'body' => $body);
			$json_body = json_decode(wp_remote_post('https://accounts.spotify.com/api/token', $args)['body'], true);
			sw_insert_auth_row($json_body['access_token'], $json_body['refresh_token']); 
			echo '<div class="sw_account_wrapper"><p>Successfully Linked Your Spotify Account</p></div></div>';

		}else{
			echo '<div class="sw_account_wrapper">
			<img src="' . plugin_dir_url(__FILE__) . 'assets/question.png">
			<p><a href="' . $url . '">Log in to Spotify</a></p>
			</div></div>';

		}
	}

	//get useable auth token with a refresh token and store in db
	function sw_get_useable_auth($refresh_token){
		$body = array('grant_type' => 'refresh_token', 'refresh_token' => $refresh_token);
		$headers = array('Authorization' => 'Basic ' . base64_encode('5eb75a94bd9d4762b7fb97cc2f262472:8e7f14fc94df4f89a460d34ee12b6fbd'));
		$args = array('headers' => $headers, 'body' => $body);
		$response = wp_remote_post('https://accounts.spotify.com/api/token', $args);
		return json_decode($response['body'], true)['access_token'];
	}

	//get a randomn track that has been recently played by the user - should return a 
	//json spotify track object that all necessary song data for widget can be extracted from 
	//users spotify client needs to not be in a private session for this all to work properly -
	//make sure to include this detail in a readme
	function sw_get_random_recent_track($access_token){
		$headers = array('Authorization' => 'Bearer ' . $access_token);
		$args = array('headers' => $headers);
		return json_decode(wp_remote_get('https://api.spotify.com/v1/me/player/recently-played?limit=1', $args)['body'], true);
	}

	//return current users full name and display name
	function sw_get_current_user_details($access_token){
		$headers = array('Authorization' => 'Bearer ' . $access_token);
		$args = array('headers' => $headers);
		return json_decode(wp_remote_get('https://api.spotify.com/v1/me', $args)['body'], true);
	}

	//load widgets' required assets
	function sw_load_css_js(){
		$plugin_url = plugin_dir_url(__FILE__);

		wp_register_style('sw_style', $plugin_url . 'assets/styles.css');
		wp_enqueue_style('sw_style');
	}

	require_once('sw_db.php');

	//create plugin table when activated
	register_activation_hook(__FILE__, 'sw_create_table');

	//remove plugin table when deactivated
	register_deactivation_hook(__FILE__, 'sw_clear_tokens');

	//add wordpress action hooks 
	add_action('init', 'sw_load_css_js');
	add_action('init', 'sw_shortcode_init');
	add_action('admin_menu', 'sw_options_page');
?>