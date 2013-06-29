<?php

/*
Plugin Name: TMG Mobile Pack
Plugin URI: 
Description: kit wordpress mobile TMG
Version: 0.1
Author: Pierre Munck
Author URI: https://github.com/PierreMunck
*/

$actual_path = explode ( ';' , get_include_path());
$new_path = realpath(dirname(__FILE__));
if(!in_array($new_path, $actual_path)){
  set_include_path (get_include_path() . PATH_SEPARATOR . $new_path);
}

// you could disable sub-plugins here
global $tmg_plugins;
$wpmp_plugins = array(
  "tmg_switcher",
  "tmg_transcoder",
  "tmg_analytics",
);

// Pre-2.6 compatibility
if (!defined('WP_CONTENT_URL')) {
  define('WP_CONTENT_URL', get_option('siteurl' . '/wp-content'));
}
if (!defined('WP_CONTENT_DIR')) {
  define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}
if (!defined('WP_PLUGIN_URL')) {
  define('WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins');
}
if (!defined('WP_PLUGIN_DIR')) {
  define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

if(!$warning=get_option('wpmp_warning')) {
  foreach($wpmp_plugins as $wpmp_plugin) {
    if (file_exists($wpmp_plugin_file = dirname(__FILE__) . "/plugins/$wpmp_plugin/$wpmp_plugin.php")) {
      include_once($wpmp_plugin_file);
    }
  }
}

register_activation_hook('tmg-mobile-pack/tmg-mobile-pack.php', 'tmg_mobile_pack_activate');
register_deactivation_hook('tmg-mobile-pack/tmg-mobile-pack.php', 'tmg_mobile_pack_deactivate');

add_action('init', 'tmg_mobile_pack_init');
add_filter('plugin_action_links', 'tmg_mobile_pack_plugin_action_links', 10, 3);

function tmg_mobile_pack_init() {

}

function tmg_mobile_pack_plugin_action_links($action_links, $plugin_file, $plugin_info) {
  $this_file = basename(__FILE__);
  if(substr($plugin_file, -strlen($this_file))==$this_file) {
    $new_action_links = array(
      "<a href='themes.php?page=tmg_switcher_admin'>Switcher</a>",
      "<a href='edit.php?page=tmg_analytics_admin'>Analytics</a> ",
    );
    foreach($action_links as $action_link) {
      if (stripos($action_link, '>Edit<')===false) {
        if (stripos($action_link, '>Deactivate<')!==false) {
          #$new_action_links[] = '<br />' . $action_link;
          $new_action_links[] = $action_link;
        } else {
          $new_action_links[] = $action_link;
        }
      }
    }
    return $new_action_links;
  }
  return $action_links;
}

function tmg_mobile_pack_activate() {
  
}

function tmg_mobile_pack_deactivate() {
  tmg_mobile_pack_hook('deactivate');
}

function tmg_mobile_pack_hook($action) {
  global $wpmp_plugins;
  foreach($wpmp_plugins as $wpmp_plugin) {
    if (function_exists($function = $wpmp_plugin . "_" . $action)) {
      call_user_func($function);
    }
  }
}

?>
