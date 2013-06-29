<?php

/*
Plugin Name: Tmg Mobile Switcher
Plugin URI: 
Description: 
Version: 0.1
Author: Pierre Munck
Author URI: https://github.com/PierreMunck
*/

define('TMG_MOBILE_WAP', 1);
define('TMG_MOBILE_HTML', 2);
define('TMG_DESKTOP', 3);
define('TMG_TABLET', 4);
define('TMG_SWITCHER_NO_SWITCH', 2);
define('TMG_SWITCHER_DESKTOP_PAGE', 3);
define('TMG_SWITCHER_MOBILE_PAGE', 4);

define('TMG_SWITCHER_CGI_VAR', 'web');

if (file_exists($theme_functions_persist = str_replace('/', DIRECTORY_SEPARATOR, get_theme_root()) . DIRECTORY_SEPARATOR . 'mobile_pack_base' . DIRECTORY_SEPARATOR . 'functions_persist.php')) {
  include_once($theme_functions_persist);
}

add_action('init', 'tmg_switcher_init');
add_action('admin_menu', 'tmg_switcher_admin_menu');
add_filter('stylesheet', 'tmg_switcher_stylesheet');
add_filter('template', 'tmg_switcher_template');
add_filter('home_url', 'tmg_switcher_home_url' );

function tmg_switcher_domain_list(){
  return array(
    'localhost' => 'Default',
    'wap.prueba.com' => 'Wap',
    'deporte.prueba.com' => 'Deporte',
  );
}

if (function_exists('add_cacheaction')) {
  // WP Super Cache integration
  if (isset($GLOBALS['wp_super_cache_debug']) && $GLOBALS['wp_super_cache_debug']) {
    wp_cache_debug("Adding hook for tmg mobile detection", 5);
  }
  add_cacheaction('wp_cache_get_cookies_values', 'tmg_switcher_wp_cache_check_mobile');
}

function tmg_switcher_home_url($url, $path = NULL, $orig_scheme = NULL, $blog_id = NULL){
  print_r($home_src);
  $home_src = explode ( '://' , get_option( 'home' ) );
  //TODO: list in config
  $list_domain = tmg_switcher_domain_list();
  if( in_array($_SERVER['HTTP_HOST'], array_keys($list_domain))){
    $url = str_replace($home_src[1],$_SERVER['HTTP_HOST'] , $url);
  }
  return $url;
}


function tmg_switcher_init() {
  //print_r($_SERVER);
  
  $list_domain = array_keys(tmg_switcher_domain_list());
  
  if(in_array($_SERVER['HTTP_HOST'], $list_domain) && $_SERVER['REQUEST_URI'] == '/'){
    $option = 'tmg_switcher_'.str_replace('.', '_', $_SERVER['HTTP_HOST']);
    $_SERVER['REQUEST_URI'] = tmg_get_term_relativ_link(get_option($option.'_category_home'));
  }
  
  switch($switcher_outcome = tmg_switcher_outcome()) {
    case TMG_SWITCHER_NO_SWITCH:
      break;
    case TMG_SWITCHER_DESKTOP_PAGE:
      // Hit log
      break;
    case TMG_SWITCHER_MOBILE_PAGE:
      // Hit log
      if (strpos(strtolower($_SERVER['REQUEST_URI']), '/wp-login.php')!==false) {
        tmg_switcher_mobile_login();
      }
      if (is_admin() || strtolower(substr($_SERVER['REQUEST_URI'], -9))=='/wp-admin') {
        tmg_switcher_mobile_admin();
      }
      break;
  }
  if(!is_admin() && $switcher_outcome!=TMG_SWITCHER_NO_SWITCH) {
    remove_filter('template_redirect', 'redirect_canonical');
  }
}

function tmg_get_term_relativ_link($term_id, $taxonomy = 'category') {
  global $wp_rewrite;
  $term = get_term($term_id,$taxonomy);
  $taxonomy = $term->taxonomy;
  $termlink = $wp_rewrite->get_extra_permastruct($taxonomy);
  $link = str_replace("%$taxonomy%", $term->slug, $termlink);
  return '/'.$link;
}

function tmg_switcher_trim_domain($domain) {
  $trimmed_domain = trim(strtolower($domain));
  if(substr($trimmed_domain, 0, 7) == 'http://') {
    $trimmed_domain = substr($trimmed_domain, 7);
  } elseif(substr($trimmed_domain, 0, 8) == 'https://') {
    $trimmed_domain = substr($trimmed_domain, 8);
  }
  $trimmed_domain = explode("/", "$trimmed_domain/");
  $trimmed_domain = $trimmed_domain[0];
  return $trimmed_domain;
}

function tmg_switcher_deactivate() {
}


function tmg_switcher_admin_menu() {
	add_theme_page(__('Mobile Switcher', 'tmg'), __('Mobile Switcher', 'tmg'), 3, 'tmg_switcher_admin', 'tmg_switcher_admin');
}

function tmg_switcher_admin() {
  if(sizeof($_POST)>0) {
    print '<div id="message" class="updated fade"><p><strong>' . tmg_switcher_options_write() . '</strong></p></div>';
  }
  include_once('tmg_switcher_admin.php');
}


function tmg_switcher_stylesheet(){
  global $curent_stylesheet;
  
  $stylesheet = get_option('stylesheet');
  if(tmg_switcher_outcome() == TMG_SWITCHER_NO_SWITCH){
    return $stylesheet;
  }
  
  if(!isset($curent_stylesheet)){
    $curent_stylesheet = $stylesheet;
    $option = 'tmg_switcher_'.str_replace('.', '_', $_SERVER['HTTP_HOST']);
    
    switch (tmg_switcher_mobile_browser()) {
      case TMG_MOBILE_WAP:
      case TMG_MOBILE_HTML:
        if(($stylesheet = get_option($option.'_mobile_theme')) != '' ) {
          $curent_stylesheet = $stylesheet;
        }
      break;
      case TMG_TABLET:
      case TMG_DESKTOP:
        if(($stylesheet = get_option($option.'_html_theme')) != '' ) {
          $curent_stylesheet = $stylesheet;
        }
      break;
    }
  }
  
  return $curent_stylesheet;
}

function tmg_switcher_template($current_template){
  global $curent_stylesheet;
  if(tmg_switcher_outcome() == TMG_SWITCHER_NO_SWITCH){
    return $current_template;
  }
  
  if(isset($curent_stylesheet)){
    return $curent_stylesheet;
  }
  
  $option = 'tmg_switcher_'.str_replace('.', '_', $_SERVER['HTTP_HOST']);
  switch (tmg_switcher_mobile_browser()) {
    case TMG_MOBILE_WAP:
    case TMG_MOBILE_HTML:
      if(($template = get_option($option.'_mobile_theme')) != '' ) {
        $current_template = $template;
      }
    break;
    case TMG_TABLET:
    case TMG_DESKTOP:
      if(($template = get_option($option.'_html_theme')) != '' ) {
        $current_template = $template;
      }
    break;
  }

  return $current_template;
}

function tmg_switcher_get_template_directory(){
  global $curent_stylesheet;
  $template = $curent_stylesheet;
  $theme_root = get_theme_root( $template );
  $template_dir = "$theme_root/$template";

  return apply_filters( 'template_directory', $template_dir, $template, $theme_root );
}
  

function tmg_switcher_outcome() {
  if(tmg_switcher_is_cgi_parameter_present()){
    return TMG_SWITCHER_NO_SWITCH;
  }
  switch (tmg_switcher_mobile_browser()) {
    case TMG_TABLET:
    case TMG_DESKTOP:
      $tmg_switcher_outcome = TMG_SWITCHER_NO_SWITCH;
      break;
    case TMG_MOBILE_WAP:
    case TMG_MOBILE_HTML:
      $tmg_switcher_outcome = TMG_SWITCHER_MOBILE_PAGE;
      break;
    default:
        $tmg_switcher_outcome = TMG_SWITCHER_NO_SWITCH;
      break;
  }
  return $tmg_switcher_outcome;
}

function tmg_switcher_mobile_browser(){
  global $tmg_switcher_mobile_browser, $mobile_desc;
  if(!isset($tmg_switcher_mobile_browser)) {
      
    include_once('lib/Mobile_Detect.php');
    include_once('lib/UAProf.php');
  
    $mobile_detect = new Mobile_Detect();
  
    $UaProf = NULL;
    if(isset($_SERVER['HTTP_X_WAP_PROFILE'])){
      $url = str_replace('\'','',$_SERVER['HTTP_X_WAP_PROFILE']);
      $url = str_replace('\\','',$url);
      $url = str_replace('"','',$url);
      $file = tmg_switcher_get_uafProf_file($url);
      $mobile_desc = new UAProf();
      $mobile_desc->process($file);
    }
  
    if($mobile_detect->isMobile()){
      if($mobile_detect->LevelAccept() > 1){
        $tmg_switcher_mobile_browser = TMG_MOBILE_HTML;
      }else{
        $tmg_switcher_mobile_browser = TMG_MOBILE_WAP;
      }
    }elseif($mobile_detect->isTablet()){
      $tmg_switcher_mobile_browser = TMG_TABLET;
    }else {
      $tmg_switcher_mobile_browser = TMG_DESKTOP;
    }
  }
  return $tmg_switcher_mobile_browser;
}


function tmg_switcher_get_uafProf_file($url) {
  $info = pathinfo($url);
  $dataDir =  dirname(__FILE__) . DIRECTORY_SEPARATOR . "UaProfData";
  $dataFile =  $dataDir . DIRECTORY_SEPARATOR . $info['basename'];
  if(!file_exists($dataFile)) {
    $data = "";
    if($handle = @fopen($url, 'r')) {
      while (!feof($handle)) {
        $data .= fread($handle, 8192);
      }
      fclose($handle);
    } elseif ($handle = @curl_init($url)) {
      curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
      $data = curl_exec($handle);
      curl_close($handle);
    }
    if(!$data) {
      return false;
    }
    @file_put_contents($dataFile, $data);
    $data = "";
  }
  return $dataFile;
}


function tmg_switcher_is_cgi_parameter_present() {
  if(isset($_GET[TMG_SWITCHER_CGI_VAR])) {
    return $_GET[TMG_SWITCHER_CGI_VAR];
  }
  return false;
}

function tmg_switcher_mobile_login() {
	include_once('pages/mobile_login.php');
	exit;
}

function tmg_switcher_mobile_admin() {
	include_once('pages/mobile_admin.php');
	exit;
}

function tmg_switcher_options_write() {
  $message = __('Settings saved.', 'tmg');
  
  foreach(tmg_switcher_domain_list() as $key => $name){
    $key = str_replace('.', '_', $key);
    $option = 'tmg_switcher_'.$key.'_html_theme';
    if(isset($_POST[$option])){
      $value = $_POST[$option];
      if(!is_array($value)) {
        $value = trim($value);
      }
      $value = stripslashes_deep($value);
      update_option($option, $value);
    }
    $option = 'tmg_switcher_'.$key.'_mobile_theme';
    if(isset($_POST[$option])){
      $value = $_POST[$option];
      if(!is_array($value)) {
        $value = trim($value);
      }
      $value = stripslashes_deep($value);
      update_option($option, $value);
    }
    $option = 'tmg_switcher_'.$key.'_category_home';
    if(isset($_POST[$option])){
      $value = $_POST[$option];
      if(!is_array($value)) {
        $value = trim($value);
      }
      $value = stripslashes_deep($value);
      update_option($option, $value);
    }
  }
  return $message;
}

function tmg_switcher_option_category($option, $onchange='') {
  //get_terms($taxonomies, $args = '')
  $options = array();
  $options[0] = '-------';
  foreach(get_terms('category', array('parent' => 0, 'get' => 'all')) as $cat) {
    $options[$cat->term_id] = $cat->name;
  }
  
  return tmg_switcher_option_dropdown($option, $options);
}

function tmg_switcher_option_themes($option, $onchange='') {
  $mobile_themes = array();
  $non_mobile_themes = array();
  foreach(wp_get_themes() as $theme => $name) {
    if(strpos(strtolower($theme), 'mobile')!==false) {
      $mobile_themes[$theme] = $name;
    } else {
      $non_mobile_themes[$theme] = $name;
    }
  }
  if(sizeof($mobile_themes)>0) {
    $mobile_themes[''] = '-------';
  }
  $options = array_merge($mobile_themes, $non_mobile_themes);
  return tmg_switcher_option_dropdown($option, $options);
}

function tmg_switcher_option_dropdown($option, $options, $onchange='') {
  if ($onchange!='') {
    $onchange = 'onchange="' . attribute_escape($onchange) . '" onkeyup="' . attribute_escape($onchange) . '"';
  }
  $dropdown = "<select id='$option' name='$option' $onchange>";
  foreach($options as $value=>$description) {
    if(get_option($option)==$value) {
      $selected = ' selected="true"';
    } else {
      $selected = '';
    }
    $dropdown .= '<option value="' . attribute_escape($value) . '"' . $selected . '>' . __($description, 'tmg') . '</option>';
  }
  $dropdown .= "</select>";
  return $dropdown;
}

function tmg_switcher_desktop_theme() {
  $info = current_theme_info();
  return $info->title;
}

?>
