<?php
/*
Plugin Name: Build a Palette
Plugin URI: http://www.gabrielcosmetics.com
Description: Build a Palette
Author: Andy Nguyen
Version: 1.0
Author URI: http://www.whatisyourm.com
*/



if( !class_exists('base_plugin') )
	require_once( dirname( __FILE__ ) . '/lib/class.base.php' );
require_once( dirname( __FILE__ ) . '/lib/class.gabriel.php' );

add_action( 'plugins_loaded', array(gabriel::get_instance(), 'init') );