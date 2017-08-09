<?php
/*
File Name: Click counter file - part of G_Buzz Button plugin
Plugin URI: http://www.muzhiks.com/google_buzz_button_wordpress
Version: 1.1.1
Author: Andrew Aronsky
Author URI: http://www.muzhiks.com/
*/
require('../../../wp-blog-header.php');
header('Location: '.$_GET['link'].'&title='.$_GET['title'].'&srcURL='.$_GET['srcURL']);
update_bd ($_GET['pid']);


function update_bd ($post_id)
	{
	global $wpdb;
	$wpdb->g_buzz = $wpdb->prefix.'g_buzz';
	$ip = get_ip();
	$post_log = $wpdb->get_var("SELECT post_id_log FROM $wpdb->g_buzz WHERE post_id = ".$post_id." ");
	$ip_db = json_decode($post_log);
	if ($ip_db != NULL)
		{
		foreach ($ip_db as $ip_s)
			{
			if ($ip_s == $ip) return 0;
			}
		}
	$ip_db[] = $ip;
	$post_log = json_encode($ip_db);
	$count = $wpdb->get_var("SELECT post_counter FROM $wpdb->g_buzz WHERE post_id = ".$post_id." ");
	$count++;
	$sql = "UPDATE $wpdb->g_buzz SET `post_counter` = '".$count."', `post_id_log` = '".$post_log."' WHERE `post_id` =".$post_id."; ";
	$wpdb->query($sql);
	return 0;
	}
	
function get_ip() 
	{
	if(isset($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
	return $_SERVER['REMOTE_ADDR'];
	}
?>