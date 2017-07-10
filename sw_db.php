<?php

	//pull latest auth and refresh tokens from the db - if they don't exist -
	//return false
	function sw_get_latest_tokens(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'sw_creds';
		$row = $wpdb->get_row("SELECT * FROM $table_name ORDER BY id DESC LIMIT 1");
		
		if(!isset($row)){
			return false;

		}else{
			return $row;

		}
	}

	//add newest refresh and auth token to table and delete the last entry
	function sw_insert_auth_row($auth_token, $refresh_token){
		global $wpdb;
		$table_name = $wpdb->prefix . 'sw_creds';
		//first delete any existing tokens 
		sw_clear_tokens();
		//insert new auth and request token
		$wpdb->insert($table_name, array('id' => '', 'auth_token' => $auth_token, 'refresh_token' => $refresh_token));
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

	//clears table of all previous tokens
	function sw_clear_tokens(){
		global $wpdb;
		$table_name = $wpdb->prefix . 'sw_creds';
		$wpdb->query("TRUNCATE $table_name");
	}



?>