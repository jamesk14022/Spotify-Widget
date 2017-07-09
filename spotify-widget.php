<?php 
/*
Plugin Name: Spotify-Widget
Plugin URI: 
Description: Easily embed a peronalised Spotify widget on your Wordpress site. 
Author: James Kingsbury
Version: 0.1
Author URI: http://vivid3d.co.uk
*/

	//setup shortcode so user can easily embed widget in wordpress installatin
	function sw_shortcode_init(){
		function sw_shortcode(){
			$plugin_url = plugin_dir_url(__FILE__);

			$access_token = sw_get_useable_auth(sw_get_latest_tokens()->refresh_token);
			$json_track = sw_get_random_recent_track($access_token);

			return '

			<div class="sw_wrapper">
			<div class="spotify-widget">
				<div class="sw_user_pic_wrapper">
					<img class="sw_user_pic" src="' . $plugin_url . 'assets/darkzy.jpg">
				</div>
				<div class="sw_user_info">
					<h4 class="sw_display_name">James Kingsbury</h4>
					<h5 class="sw_username">@jamesk14022</h5>
				</div>

				<div class="sw_track_label_wrapper">
					<h6 class="sw_track_label">Recently Listened To</h6>
				</div>
				<hr>
				<div class="sw_track_cover">
					<img src="' . $plugin_url . 'assets/muse_cover.jpg">
				</div>
				<div class="sw_track_info">
					<h4 class="sw_track_name">' . $json_track['items'][0]['track']['artists'][0]['name'] . '</h4>
					<h5 class="sw_track_artist">Muse</h5>
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
		$url = 'https://accounts.spotify.com/authorize/?client_id=5eb75a94bd9d4762b7fb97cc2f262472&response_type=code&redirect_uri=http://localhost:8888/wp-admin/options-general.php?page=sw&scope=user-read-recently-played'
		?>
		<div class="wrap">
			<h1><?= esc_html(get_admin_page_title()); ?></h1>
			<a href="<?= $url ?>">Log In</a>

			<!-- check if auth code or refresh code is stored  -->



			<!-- check if access token is present  -->
			<?php if(isset($_GET['code'])){
					$body = array('grant_type' => 'authorization_code', 'code' => $_GET['code'], 'redirect_uri' => 'http://localhost:8888/wp-admin/options-general.php?page=sw');
					$headers = array('Authorization' => 'Basic ' . base64_encode('5eb75a94bd9d4762b7fb97cc2f262472:8e7f14fc94df4f89a460d34ee12b6fbd'));
					$args = array('headers' => $headers, 'body' => $body);
					$post_token_url = 'https://accounts.spotify.com/api/token';
				$response = wp_remote_post($post_token_url, $args);
				$json_body = json_decode($response['body'], true);

				sw_insert_auth_row($json_body['access_token'], $json_body['refresh_token']); 
			}?>

			<!-- if no codes or tokens present, give option to login -->


		</div>
		<?php
	}

	//function to create table that the current auth and refresh tokens will be stored in 
	function sw_create_table(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'sw_creds';
		$charset_collate = $wpdb->get_charset_collate();

		//crete table query
		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL PRIMARY KEY AUTO_INCREMENT,
			auth_token text NOT NULL,
  			refresh_token text NOT NULL
		) $charset_collate;";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	//add newest refresh and auth token to table and delete the last entry
	function sw_insert_auth_row($auth_token, $refresh_token){
		global $wpdb;
		$table_name = $wpdb->prefix . 'sw_creds';

		// //delete existing entry so we aren't storing unnessesary tokens - need to get this working before release 
		// so Im not holding loads of useless tokens 
		// $wpdb->delete($table_name, array('id' => '*'));

		//insert new auth and request token
		$wpdb->insert($table_name, array('id' => '', 'auth_token' => $auth_token, 'refresh_token' => $refresh_token));
	}

	//pull latest auth and refresh tokens from the db
	function sw_get_latest_tokens(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'sw_creds';

		return $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");
	}

	//get useable auth token with a refresh token and store in db
	function sw_get_useable_auth($refresh_token){
		$body = array('grant_type' => 'refresh_token', 'refresh_token' => $refresh_token);
		$headers = array('Authorization' => 'Basic ' . base64_encode('5eb75a94bd9d4762b7fb97cc2f262472:8e7f14fc94df4f89a460d34ee12b6fbd'));
		$args = array('headers' => $headers, 'body' => $body);
		$post_token_url = 'https://accounts.spotify.com/api/token';
		$response = wp_remote_post($post_token_url, $args);
		$json_body = json_decode($response['body'], true);

		return $json_body['access_token'];
	}

	//get a randomn track that has been recently played by the user - should return a 
	//json spotify track object that all necessary data for widget can be extracted from 
	//users spotify client needs to not be in a private session for this all to work properly -
	//make sure to include this detail in a readme
	function sw_get_random_recent_track($access_token){
		$headers = array('Authorization' => 'Bearer ' . $access_token);
		$args = array('headers' => $headers);
		$post_token_url = 'https://api.spotify.com/v1/me/player/recently-played?limit=1';
		$response = wp_remote_get($post_token_url, $args);
		return json_decode($response['body'], true);
	}

	//load widgets' required assets
	function sw_load_css_js(){
		$plugin_url = plugin_dir_url(__FILE__);

		wp_register_style('sw_style', $plugin_url . 'assets/styles.css');
		wp_enqueue_style('sw_style');
	}

	//create plugin table when activated
	register_activation_hook(__FILE__, 'sw_create_table');

	//add wordpress action hooks 
	add_action('init', 'sw_load_css_js');
	add_action('init', 'sw_shortcode_init');
	add_action('admin_menu', 'sw_options_page');
?>