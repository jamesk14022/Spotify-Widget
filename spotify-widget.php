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
					<h4 class="sw_track_name">Undisclosing Desires</h4>
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
		?>
		<div class="wrap">
			<h1><?= esc_html(get_admin_page_title()); ?></h1>
		</div>
		<?php
	}

	//load widgets required assets
	function sw_load_css_js(){
		$plugin_url = plugin_dir_url(__FILE__);

		wp_register_style('sw_style', $plugin_url . 'assets/styles.css');
		wp_enqueue_style('sw_style');
	}


	//add wordpress action hooks 
	add_action('init', 'sw_load_css_js');
	add_action('init', 'sw_shortcode_init');
	add_action('admin_menu', 'sw_options_page');
?>