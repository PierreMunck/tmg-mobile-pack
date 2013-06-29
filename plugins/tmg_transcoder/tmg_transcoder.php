<?php

/*
$Id: tmg_transcoder.php 567923 2012-07-05 18:10:01Z jamesgpearce $

$URL: http://plugins.svn.wordpress.org/wordpress-mobile-pack/trunk/plugins/tmg_transcoder/tmg_transcoder.php $

Copyright (c) 2009 James Pearce & friends, portions mTLD Top Level Domain Limited, ribot, Forum Nokia

Online support: http://wordpress.org/extend/plugins/wordpress-mobile-pack/

This file is part of the WordPress Mobile Pack.

The WordPress Mobile Pack is Licensed under the Apache License, Version 2.0
(the "License"); you may not use this file except in compliance with the
License.

You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed
under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
CONDITIONS OF ANY KIND, either express or implied. See the License for the
specific language governing permissions and limitations under the License.
*/

/*
Plugin Name: Mobile Transcoder
Plugin URI: http://wordpress.org/extend/plugins/wordpress-mobile-pack/
Description: Rewrites blog pages and posts for the mobile theme, to ensure compatibility with mobile devices
Version: 1.2.5
Author: James Pearce & friends
Author URI: http://www.assembla.com/spaces/wordpress-mobile-pack
*/

function tmg_transcoder_activate() {
  if(!is_writable($dir = $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'c')) {
    update_option('tmg_warning', sprintf(__('<strong>Transcoder will not be able to cache images</strong> to %s.', 'tmg'), $dir) . ' ' . __('Please ensure that the web server has write-access to that directory.', 'tmg'));
  }
}

function tmg_transcoder_remove_media(&$content) {

	// in some cases we might know what the tag wants to do, so we can replace it
	// with something good like a link to the mobile site of YouTube no need to
	// replace vimeo as the embedding code already comes with a nice link in case
	// the object is not supported or removed
  $patterns_to_replace = array(
    '/<object.*movie\"\ value=\"http\:\/\/(www\.|m\.)?youtube\.com\/(watch\?v=|v\/)(\w+).*\">.*\/object>/i',
  );
  $replacements = array(
    '<a href="http://m.youtube.com/#/watch?v=${3}">YouTube video</a>',  // replace the youtube embedding object with a link to the mobile page
  );
  $content = preg_replace($patterns_to_replace, $replacements, $content);

  $remove_tags = array(
    "script"=>true,
    "object"=>false,
    "embed"=>false,
    "marquee"=>false,
    "frame"=>false,
    "iframe"=>false,
  );

  $remove_attributes = array(
    "on[^=]*",
  );

  foreach($remove_tags as $remove_tag=>$and_inner) {
    if($and_inner) {
      $content = preg_replace("/\<$remove_tag.*\<\/$remove_tag"."[^>]*\>/Usi", "", $content);
    }
    $content = preg_replace("/\<\/?$remove_tag"."[^>]*\>/Usi", "", $content);
  }

  foreach($remove_attributes as $remove_attribute) {
    $content = preg_replace("/(\<[^>]*)(\s$remove_attribute=\\\".*\\\")/Usi", '$1', $content);
    $content = preg_replace("/(\<[^>]*)(\s$remove_attribute=\'.*\')/Usi", '$1', $content);
  }

}

function tmg_transcoder_partition_pages(&$content) {
  global $tmg_transcoder_is_last_page;
  $pages = tmg_transcoder_weigh_paragraphs($content);
  if(!isset($_GET['tmg_tp']) || !is_numeric($page = $_GET['tmg_tp'])) {
    $page = 0;
  }
  if($page >= sizeof($pages)) {
    $page = sizeof($pages)-1;
  }
  if($page < 0) {
    $page = 0;
  }
  $pager = '';
  if(sizeof($pages)>1) {
    $pager = "<p>" . sprintf(__('Page %1$d of %2$d', 'tmg'), $page+1, sizeof($pages));
    if ($page>0) {
      $previous .= "<a href='" . tmg_transcoder_replace_cgi("tmg_tp", $page-1) . "'>" . __('Previous page', 'tmg') . "</a>";
    }
    if ($page<sizeof($pages)-1) {
      $next .= "<a href='" . tmg_transcoder_replace_cgi("tmg_tp", $page+1) . "'>" . __('Next page', 'tmg') . "</a>";
      $tmg_transcoder_is_last_page = false;
    } else {
      $tmg_transcoder_is_last_page = true;
    }
    if($previous || $next) {
      $pager .= " | $previous";
      if($previous && $next) {
        $pager .= " | ";
      }
      $pager .= $next;
    }
    $pager .= "</p>";
  }
  $content = "<p>" . @implode("</p><p>", $pages[$page]) . "</p>$pager";
}


function tmg_transcoder_is_last_page() {
  global $tmg_transcoder_is_last_page;
  if(isset($tmg_transcoder_is_last_page)) {
    return $tmg_transcoder_is_last_page;
  }
  return true;
}

function tmg_transcoder_shrink_images(&$content) {
  if(!function_exists('imagecreatetruecolor')) {
    return;
  }
  $content = preg_replace("/\<\/img*\>/Usi", "", $content);
  preg_match_all("/\<img.* src=((?:'[^']*')|(?:\"[^\"]*\")).*\>/Usi", $content, $images);
  foreach($images[0] as $img_index => $image) {
    $src = $images[1][$img_index];
    $new_src = trim($src, "'\"");
    $new_src = tmg_transcoder_url_join('http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], $new_src);
    $height = "";
    $width = "";
    $new_image = $image;
    preg_match_all("/(width|height)[=:'\"\s]*(\d+)(?:px|[^\d])/Usi", $image, $dimensions);
    foreach($dimensions[0] as $dimension_index=>$dimension_clause) {
      $$dimensions[1][$dimension_index] = $dimensions[2][$dimension_index];
      $new_image = str_replace($dimension_clause, "", $new_image);
    }
    if(!$height || !$width) { //er, where did these come from anyway? the magic of $$ ;-)
      tmg_transcoder_fetch_image($new_src, $location);
    }
    $max_width = tmg_transcoder_max_screen_width();
    if($width>$max_width) {
      $height = floor($height * $max_width / $width);
      $width = $max_width;
    }
    $max_height = tmg_transcoder_max_screen_height();
    if($height>$max_height) {
      $width = floor($width * $max_height / $height);
      $height = $max_height;
    }
    $new_src = tmg_transcoder_convert_image($new_src, $width, $height);
    if($new_src !== FALSE){
      $new_image = str_replace($src, "'$new_src'", $new_image);
      $new_image = "<img width='$width' height='$height'" . substr($new_image, 4);
    }else{
      $new_image = "";
    }
    $content = str_replace($image, $new_image, $content);
  }
}


function tmg_transcoder_simplify_styling(&$content) {

  $remove_attributes = array(
    "align",
    "background",
    "bgcolor",
    "border",
    "cellpadding",
    "cellspacing",
    "class",
    "color",
    "height",
    "style",
    "width",
  );

  $remove_tags = array(
    "center",
    "font",
    "span",
    "style"
  );

  $remove_empty_tags = array(
    "\w*",
  );

  foreach($remove_attributes as $remove_attribute) {
    $content = preg_replace("/(\<[^>]*)(\s$remove_attribute=\\\".*\\\")/Usi", '$1', $content);
    $content = preg_replace("/(\<[^>]*)(\s$remove_attribute=\'.*\')/Usi", '$1', $content);
  }

  foreach($remove_tags as $remove_tag) {
    $content = preg_replace("/\<\/?$remove_tag"."[^>]*\>/Usi", "", $content);
  }

  foreach($remove_empty_tags as $remove_empty_tag) {
    $content = preg_replace("/\<{$remove_empty_tag}\s*\>\<\/{$remove_empty_tag}\s*\>/Usi", "", $content);
  }
}



function tmg_transcoder_replace_cgi($key, $new_value) {
  $new_get = array();
  foreach($_GET as $get=>$value) {
    if($get!=$key) {
      $new_get[$get] = urlencode($get) . "=" . urlencode(stripslashes($value));
    }
  }
  $new_get[$key] = urlencode($key) . "=" . urlencode($new_value);
  return array_shift(explode("?", $_SERVER['REQUEST_URI'])) . "?" . implode("&amp;", $new_get);
}

function tmg_transcoder_weigh_paragraphs($content) {
  $contiguous_tags = array(
    "ul"=>false,
    "ol"=>false,
    "div"=>false,
    "code"=>false,
  );
  $content = trim($content);
  foreach($contiguous_tags as $contiguous_tag=>$save_breaks) {
    preg_match_all("/\<{$contiguous_tag}.*<\/{$contiguous_tag}[^>]*\>/Usi", $content, $blocks);
    foreach($blocks[0] as $block) {
      $new_block = tmg_transcoder_normalise_breaks($block);
      if($save_breaks) {
        $new_block = str_replace("\n", "<tmgbr />", $new_block);
      } else {
        $new_block = str_replace("\n", " ", $new_block);
      }
      $content = str_replace($block, $new_block, $content);
    }
  }

  $content = tmg_transcoder_normalise_breaks($content);
  $content = explode("\n", $content);
  $weights = array();
  $total_weight = 0;
  $max_weight = tmg_transcoder_max_paragraph_weight();
  $paragraphs = array();
  foreach($content as $paragraph) {
    $paragraph = trim($paragraph);
    $paragraph = balanceTags($paragraph, true);
    if ($paragraph!='') {
      $weight = strlen($paragraph);
      if (strpos(strtolower($paragraph), "<img")) {
        $weight += 300;
      }
      $total_weight += $weight;
      if($weight > $max_weight) {
        $max_weight = $weight;
      }
      $weights[] = $weight;
      $paragraphs[] = $paragraph;
    }
  }
  $pages = array();
  $page = 0;
  $page_weight = 0;
  foreach($paragraphs as $p=>$paragraph) {
    if($page_weight + $weights[$p] > $max_weight) {
      $page++;
      $page_weight = 0;
    }
    $pages[$page][] = str_replace("<tmgbr />", "<br />", $paragraph);
    $page_weight += $weights[$p];
  }
  return $pages;
}

function tmg_transcoder_normalise_breaks($content) {
  $content = preg_replace("/\r/Usi", "\n", $content);
  $content = preg_replace("/\<\/?p[^>]*\>/Usi", "\n", $content);
  $content = preg_replace("/\<\/?br[^>]*\>/Usi", "\n", $content);
  $content = preg_replace("/\n+/Usi", "\n", $content);
  $content = preg_replace("/[\x20\x09]+/Usi", " ", $content);
  return $content;
}


function tmg_transcoder_max_paragraph_weight() {
  $default = 5000;
  if(function_exists('tmg_deviceatlas_enabled') && tmg_deviceatlas_enabled()) {
    $memory = tmg_deviceatlas_property('memoryLimitMarkup');
    if(!is_numeric($memory)) {
      return $default;
    }
    if($memory==0) {
      return 10000;
    }
    if($memory<3000) {
      return $default;
    }
    if($memory>15000) {
      return 10000;
    }
    return floor($memory * 0.66);
  }
  return $default;
}
function tmg_transcoder_max_screen_width() {
  global $mobile_desc;
  $default = 300;
  if(isset($mobile_desc)) {
    $width = $mobile_desc->getInfo('screenwidth');
    if(!is_numeric($width)) {
      return $default;
    }
    if($width<40) {
      return 40;
    }
    if($width>300) {
      return 300;
    }
    return $width - 4;
  }
  return $default;
}
function tmg_transcoder_max_screen_height() {
  global $mobile_desc;
  $default = 80;
  if(isset($mobile_desc)) {
    $height = $mobile_desc->getInfo('screenheight');
    if(!is_numeric($height)) {
      return $default;
    }
    if($height<40) {
      return 40;
    }
    if($height>300) {
      return 300;
    }
    return $height - 4;
  }
  return $default;
}


function tmg_transcoder_url_is_dot($val) {
  return $val != '.';
}

function tmg_transcoder_url_join($base, $url) {
  $base = parse_url($base);
  $url = parse_url($url);

  if ($url['scheme']) {
    return tmg_transcoder_url_unparse($url);
  }

  if (!($url['path'] || $url['query'] || $url['fragment'])) {
    return tmg_transcoder_url_unparse($base);
  }

  if (substr($url['path'], 0, 1) == '/') {
    $base['path'] = $url['path'];
    return tmg_transcoder_url_unparse($base);
  }

  $base['query'] = $url['query'];
  $base['fragment'] = $url['fragment'];

  $segments = explode('/', $base['path']);
  array_pop($segments);
  $segments = array_merge($segments, explode('/', $url['path']));
  if ($segments[sizeof($segments) - 1] == '.') {
    $segments[sizeof($segments) - 1] = '';
  }

  $segments = array_filter($segments, 'tmg_transcoder_url_is_dot');

  while (true) {
    $i = 1;
    $n = sizeof($segments) - 1;
    while ($i < $n) {
      if ($segments[$i] == '..' &&
        $segments[$i-1] != '' &&
        $segments[$i-1] != '..') {
        unset($segments[$i]);
        unset($segments[$i-1]);
        break;
      }
      $i ++;
    }
    if ($i >= $n) {
      break;
    }
  }
  $cnt = sizeof($segments);
  if ($cnt == 2 && $segments[0] == '' && $segments[1] == '..') {
    $segments[1] = '';
  } elseif ($cnt >= 2 && $segments[$cnt - 1] == '..') {
    unset($segments[$cnt - 1]);
    $segments[$cnt - 2] = '';
  }
  $base['path'] = implode('/', $segments);
  return tmg_transcoder_url_unparse($base);
}

function tmg_transcoder_url_unparse($url) {
  if($url['scheme']) {
    $result = $url['scheme'] . '://';
  }
  if (@$url['user'] || @$url['pass']) {
    $result .= $url['user'] . ':' . $url['pass'] . '@';
  }
  $result .= $url['host'] . $url['path'];
  if (@$url['query']) {
    $result .= '?' . $url['query'];
  }
  if (@$url['fragment']) {
    $result .= '#' . $url['fragment'];
  }
  return $result;
}


function tmg_transcoder_fetch_image($url, &$location) {
  $info = pathinfo($url);
  $extension = $info['extension'];
  $location =  dirname(__FILE__) . DIRECTORY_SEPARATOR . "c" . '/' . md5($url).'.'.$extension;
  if(!file_exists($full_location)) {
    $data = "";
    if($handle = @fopen($url, 'r')) {
      while (!feof($handle)) {
        $data .= fread($handle, 8192);
      }
      fclose($handle);
    } elseif ($handle = @curl_init($url)) {
      curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
      $data = curl_exec($handle);
      $retunrinfo = curl_getinfo($handle);
      if(isset($retunrinfo['http_code']) && $retunrinfo['http_code'] == 404){
        $data = NULL;
      } 
      curl_close($handle);
    }
    if(!$data) {
      return FALSE;
    }
    @file_put_contents($location, $data);
    $data = "";
  }
  return TRUE;
}

if (!function_exists('file_put_contents')) {
  function file_put_contents($filename, $data) {
    $f = @fopen($filename, 'w');
    if (!$f) {
      return false;
    } else {
      $bytes = fwrite($f, $data);
      fclose($f);
      return $bytes;
    }
  }
}


/**
 * converter de imagenes
 */
function tmg_transcoder_convert_image($url, $width, $height) {
  include_once('lib/TmgImage.php');
  if (tmg_transcoder_fetch_image($url, $location) === false) {
    return FALSE;
  }
  $base_url = get_option('home') . "/wp-content/plugins/tmg-mobile-pack/plugins/tmg_transcoder/c";
  if ($width==$_w && $height==$_h) {
    return "$base$location";
  }
  $dir_location = dirname($location);
  $toolImg = new TmgImage($base_url,$dir_location,$type);
  return $toolImg->getImage($location,$width);
    
  /*if(!file_exists($full_file = dirname(__FILE__) . DIRECTORY_SEPARATOR . ($file = "$location.$width.$height.$type"))) {
    
    $source = @imagecreatefromstring(file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . $location));
    @imagealphablending($source, true);
    @imagesavealpha($source, true);
    $image = @imagecreatetruecolor($width, $height);
    @imagealphablending($image, false);
    @imagesavealpha($image, true);
    @imagecopyresampled($image, $source, 0, 0, 0, 0, $width, $height, $_w, $_h);
    @imagealphablending($image, true);
    @imagedestroy($source);
    switch($type) {
      case 'gif':
        imagegif($image, $full_file);
        break;
      case 'jpg':
        imagejpeg($image, $full_file);
        break;
      case 'png':
        imagepng($image, $full_file);
        break;
    }
    @imagedestroy($image);
  }
  return "$base$file";*/
}


function tmg_transcoder_purge_cache() {
  $count = 0;

  $dir_handle = opendir($dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'c');
  while($file = readdir($dir_handle)) {
    if($file[0]!=".") {
      if(@unlink($dir . DIRECTORY_SEPARATOR . $file)) {
        $count++;
      }
    }
  }
  closedir($dir_handle);
  return $count;
}
?>
