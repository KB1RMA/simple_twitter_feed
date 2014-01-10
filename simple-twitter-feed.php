<?php
/**
 * @package   Simple Twitter Feed
 * @author    Chris Snyder <chris@keyedupmedia.com>
 * @license   GPL-2.0
 * @link      http://keyedupmedia.com
 * @copyright 2013 Keyed-Up Media, LLC
 *
 * @wordpress-plugin
 * Plugin Name: Simple Twitter Feed
 * Plugin URI:  TODO
 * Description: TODO
 * Version:     1.0.0
 * Author:      TODO
 * Author URI:  TODO
 * Text Domain: simple-twitter-feed-locale
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /lang
 */

include_once('vendor/twitteroauth.php');
include_once('vendor/OAuth.php');

// If this file is called directly, abort.
if (!defined( 'WPINC'))
	die;

// Include the plugin
require_once(plugin_dir_path( __FILE__ ) .'class-simple-twitter-feed.php');

// Include the widget
require_once(plugin_dir_path( __FILE__ ) .'class-simple-feed-widget.php');

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array('Simple_Twitter_Feed', 'activate' ));
register_deactivation_hook( __FILE__, array('Simple_Twitter_Feed', 'deactivate'));

Simple_Twitter_Feed::get_instance();