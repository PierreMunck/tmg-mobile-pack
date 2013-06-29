<?php

/*
Plugin Name: Tmg Mobile Analytics
Plugin URI: 
Description: 
Version: 0.1
Author: Pierre Munck
Author URI: https://github.com/PierreMunck
*/

add_action('init', 'tmg_analytics_init');
add_action('admin_menu', 'tmg_analytics_admin_menu');
add_action('wp_footer', 'tmg_analytics_wp_footer');

function tmg_analytics_init() {
  if(($provider_id=get_option('tmg_analytics_provider_id'))=='') {
    return;
  }
  switch (get_option('tmg_analytics_provider')) {
  }
}

function tmg_analytics_activate() {
  foreach(array(
    'tmg_analytics_provider'=>'',
    'tmg_analytics_provider_id'=>'',
  ) as $name=>$value) {
    if (get_option($name)=='') {
      update_option($name, $value);
    }
  }
}

function tmg_analytics_wp_footer() {
  if(($provider_id=get_option('tmg_analytics_provider_id'))=='') {
    return;
  }
  print "<span id='tmg_analytics'>";
  switch (get_option('tmg_analytics_provider')) {
  }
  print "</span>";
}

function tmg_analytics_admin_menu() {
	add_management_page(__('Mobile Analytics', 'tmg'), __('Mobile Analytics', 'tmg'), 3, 'tmg_analytics_admin', 'tmg_analytics_admin');

}
function tmg_analytics_admin() {
  if(sizeof($_POST)>0) {
    print '<div id="message" class="updated fade"><p><strong>' . tmg_analytics_options_write() . '</strong></p></div>';
    if(isset($_POST['tmg_analytics_local_reset']) && $_POST['tmg_analytics_local_reset']=='true') {
      if (tmg_analytics_local_enabled()) {
        tmg_switcher_hit_reset();
        print '<div id="message" class="updated fade"><p><strong>' . __('Hit counter reset.', 'tmg') . '</strong></p></div>';
      }
    }
  }
  include_once('tmg_analytics_admin.php');
}


function tmg_analytics_options_write() {
  $message = __('Settings saved.', 'tmg');
  foreach(array(
    'tmg_analytics_provider'=>false,
    'tmg_analytics_provider_id'=>false,
  ) as $option=>$checkbox) {
    if(isset($_POST[$option])){
      $value = $_POST[$option];
      if(!is_array($value)) {
  			$value = trim($value);
      }
			$value = stripslashes_deep($value);
      update_option($option, $value);
    } elseif ($checkbox) {
      update_option($option, 'false');
    }
  }
  return $message;
}

function tmg_analytics_option($option, $onchange='', $class='', $style='') {
  switch ($option) {
    case 'tmg_analytics_provider':
      return tmg_analytics_option_dropdown(
        $option,
        array(
          'none'=>__('Disabled', 'tmg'),
        ),
        $onchange
      );
    case 'tmg_analytics_provider_id':
      return tmg_analytics_option_text(
        $option,
        $onchange,
        $class,
        $style
      );
    case 'tmg_analytics_local_reset':
      return tmg_analytics_option_checkbox(
        $option,
        $onchange
      );
  }
}


function tmg_analytics_option_dropdown($option, $options, $onchange='') {
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

function tmg_analytics_option_text($option, $onchange='', $class='', $style='') {
  if ($onchange!='') {
    $onchange = 'onchange="' . attribute_escape($onchange) . '" onkeyup="' . attribute_escape($onchange) . '"';
  }
  if ($class!='') {
    $class = 'class="' . attribute_escape($class) . '"';
  }
  if ($style!='') {
    $style = 'style="' . attribute_escape($style) . '"';
  }
  $text = '<input type="text" id="' . $option . '" name="' . $option . '" value="' . attribute_escape(get_option($option)) . '" ' . $onchange . ' ' . $class . ' ' . $style . '/>';
  return $text;
}

function tmg_analytics_option_checkbox($option, $onchange='') {
  if ($onchange!='') {
    $onchange = 'onchange="' . attribute_escape($onchange) . '"';
  }
  $checkbox = '<input type="checkbox" id="' . $option . '" name="' . $option . '" value="true" ' . (get_option($option)==='true'?'checked="true"':'') . ' ' . $onchange . ' />';
  return $checkbox;
}

function tmg_analytics_local_enabled() {
  return function_exists('tmg_switcher_hit_reset');
}
function tmg_analytics_local_summary() {
  return tmg_switcher_hit_summary();
}
?>
