<?php

/*
$Id: mobile_admin.php 195195 2010-01-19 04:11:37Z jamesgpearce $

$URL: http://plugins.svn.wordpress.org/wordpress-mobile-pack/trunk/plugins/tmg_switcher/pages/mobile_admin.php $

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

  include_once('mobile.php');
  tmg_ms_mobile_admin();

  function tmg_ms_mobile_admin() {
    $base = get_option('home');

    if (($user = wp_get_current_user())==null || $user->ID==0) {
      header("Location: $base/wp-login.php?redirect_to=" . urlencode($base) . "%2Fwp-admin%2F");
    }

    $menu = array(
      __("Overview", 'tmg') => "/wp-admin/index.php",
      __("New post", 'tmg') => "/wp-admin/post-new.php",
      __("Edit post", 'tmg') => "/wp-admin/post.php?action=edit",
      __("Comments", 'tmg') => "/wp-admin/edit-comments.php",
      "_" . __("Comment", 'tmg') => "/wp-admin/comment.php",
      __("Switcher", 'tmg') => "/wp-admin/themes.php",
      __("Settings", 'tmg') => "/wp-admin/options-general.php",
    );
    if (function_exists('wp_logout_url')) {
      $menu[__("Log out", 'tmg')] = wp_logout_url();
    } else {
      $menu[__("Log out", 'tmg')] = "/wp-login.php?action=logout";
    }

    $page = $_SERVER['REQUEST_URI'];
    $function = "";
    foreach($menu as $link) {
      if(strpos(strtolower($page), strtolower($link))!==false) {
        $function = substr($link, 10);
        $function = explode(".", $function);
        $function = str_replace("-", "_", $function[0]);
        $function = strtolower($function);
        break;
      }
    }
    if(!function_exists("tmg_msma_$function")) {
      $function = "overview";
    }
    if(!current_user_can('manage_options')) { // harsh but fair
      $menu = array();
      $function = "junior";
    }

    tmg_ms_mobile_top("Admin", $menu);
    call_user_func("tmg_msma_$function", $menu);
    tmg_ms_mobile_bottom();
  }

  function tmg_msma_overview($menu) {
    $base = get_option('home');
    $post_count = wp_count_posts('post');
    $page_count = wp_count_posts('page');
    print "<p>";
    printf(__ngettext("You have one post", "You have %d posts", $c=0+($post_count->publish), 'tmg'), $c);
    print ' ';
    printf(__ngettext("and one page", "and %d pages", $c=0+($page_count->publish), 'tmg'), $c);
    print ' ' . __("contained within", 'tmg') . ' ';
    printf(__ngettext("one category", "%d categories", $c=0+(wp_count_terms('category')), 'tmg'), $c);
    print ' ' . __("and", 'tmg') . ' ';
    printf(__ngettext("one tag", "%d tags", $c=0+(wp_count_terms('post_tag')), 'tmg'), $c);
    print ".</p>";
  	global $wpdb;
		$comments = $wpdb->get_results("SELECT count(*) as cnt FROM $wpdb->comments WHERE comment_approved='0'" );
    $comment_count = $comments[0];
    printf("<p>" . __ngettext("You have one comment to moderate", "You have %d comments to moderate", $c=0+($comment_count->cnt), 'tmg') . ".</p>", $c);
    print "<h3>" . __("Select an admin page:", 'tmg') . "</h3>";
    print "<p><ul>";
    $not_first = false;
    foreach($menu as $name=>$link) {
      if($name[0]!='_' && $not_first) {
        if(substr($link, 0, 7)!="http://" && substr($link, 0, 8)!="https://") {
          $link = $base . $link;
        }
        print "<li><a href='$link'>" . __("$name", 'tmg') . "</a>";
      }
      $not_first = true;
    }
    print "</ul></p>";
    print "<p>" . sprintf(__("...or <a%s>return to the site</a>", 'tmg'), " href='$base/'") . "</p>";
    print "<p>" . __("A subset of the full WordPress administration is available through this mobile interface.", 'tmg') . "</p>";
  }

  function tmg_msma_junior($menu) {
    $base = get_option('home');
    print "<h3>" . __("Sorry! Permission denied...", 'tmg') . "</h3>";
    print "<p>" . __("Only 'administrator' users can use the mobile admin panel.", 'tmg') . "</p>";
    print "<p><a href='" . get_option('siteurl') . "$base/wp-login.php?action=logout'>" . __("Login as a different user", 'tmg') . "</a> " . __("or", 'tmg') . " <a href='$base/'>" . __("return to the site", 'tmg') . "</a></p>";
  }

  function tmg_msma_post_new() {
    tmg_msma_post(null, true);
  }
  function tmg_msma_post($menu, $new = false) {
   	if (sizeof($_POST) > 0) {
      if (!tmg_msma_check_referer()) { return; }
      @wp_update_post($_POST);
      print "<p>" . __("Your changes have been applied.", 'tmg') . "</p>";
      tmg_msma_post_list();
    } else {
      if(is_numeric($id = @$_GET['post'])) {
        $post = get_post($id, OBJECT, 'edit');
        if(!$post->ID) {
          print "<p>" . __("That post does not exist, but you may write a new one.", 'tmg') . "</p>";
        }
      } elseif (!$new) {
        tmg_msma_post_list();
        return;
      }
      if(!@$post->ID) {
      	$post->ID = 0;
        $post->post_status = 'draft';
      }
      tmg_msma_post_edit_form($post);
    }
  }

  function tmg_msma_post_edit_form($post) {
    global $user_ID;
    print '<form name="post" action="' . $_SERVER['REQUEST_URI'] . '" method="post" id="post">';

    print '<input type="hidden" name="ID" value="' . $post->ID . '" />';
    print '<input type="hidden" name="user_ID" value="' . (int) $user_ID . '" />';

    print '<p><label for="title">' . __('Title', 'tmg') . ':</label><br />';
    print '<input type="text" name="post_title" value="' . attribute_escape(@$post->post_title) . '" id="title" /></p>';

    print '<p><label for="post_status">' . __('Status', 'tmg') . ':</label><br />';
    print '<select name="post_status" id="post_status">';
    print '<option ' . (($post->post_status == 'publish' || $post->post_status == 'private') ? 'selected="selected"' : "") . ' value="publish">' . __('Published', 'tmg') . '</option>';
    print '<option ' . (($post->post_status == 'draft' || $post->post_status == 'future') ? 'selected="selected"' : "") . ' value="draft">' . __('Unpublished', 'tmg') . '</option>';
    print '<option ' . (($post->post_status == 'pending') ? 'selected="selected"' : "") . ' value="pending">' . __('Pending Review', 'tmg') . '</option>';
    print '</select></p>';

    print '<p><label for="post_content">' . __('Content', 'tmg') . ':</label><br />';
    $safe_content = @$post->post_content;
    $safe_content = str_replace("<textarea", "<div", $safe_content);
    $safe_content = str_replace("<TEXTAREA", "<div", $safe_content);
    $safe_content = str_replace("</textarea", "</div", $safe_content);
    $safe_content = str_replace("</TEXTAREA", "</div", $safe_content);
    print '<textarea name="post_content" id="post_content" rows="6">' . $safe_content . '</textarea></p>';
    print '<input class="button" name="submit" type="submit" id="submit" value="' . __('Apply', 'tmg') . '" />';
    print '<p>' . __('You can use HTML tags to format your post. Use &lt;!--more--&gt; to indicate the end of the teaser.', 'tmg') . '</p>';

    print '</form>';
  }

  function tmg_msma_post_list() {
    $base = get_option('home');
    wp('orderby=modified');
    if(have_posts()) {
      global $post;
      print "<p>" . __("Select a post to edit:", 'tmg') . "</p>";
      add_filter('get_pagenum_link', 'tmg_msma_get_pagenum_link');
      while (have_posts()) {
        the_post();
        print "<p>" .
          "<strong><a href='$base/wp-admin/post.php?action=edit&post=$post->ID'>" . get_the_title() . "</a></strong>" .
          "<br />" . get_the_modified_date() .
          "<br />" . tmg_msma_post_status($post->post_status) .
          "</p>";
      }
      next_posts_link('Older');
      previous_posts_link('Newer');
     } else {
      print "<p>" . __("There are no posts to edit.", 'tmg') . "</p>";
    }
  }
  function tmg_msma_edit_comments() {
  	global $wpdb;
		$comments = $wpdb->get_results("SELECT $wpdb->comments.*, $wpdb->posts.post_title FROM $wpdb->comments INNER JOIN $wpdb->posts ON $wpdb->comments.comment_post_id = $wpdb->posts.id WHERE comment_approved='0' ORDER BY comment_date_gmt DESC LIMIT 5" );
    if(sizeof($comments)==0) {
      print "<p>" . __("This site has no comments awaiting moderation.", 'tmg') . "</p>";
    } else {
      switch($size = sizeof($comments)) {
        case 5:
          print "<p>" . __("There are at least 5 comments awaiting moderation:", 'tmg') . "</p>";
          break;
        case 1:
          return tmg_msma_edit_comment($comments[0], true);
        default:
          print "<p>" . __("There are $size comments awaiting moderation:", 'tmg') . "</p>";
      }
      foreach($comments as $comment) {
        tmg_msma_edit_comment($comment);
      }
    }
  }
  function tmg_msma_comment() {
    $id = $_GET['c'];
    if(is_numeric($id)) {
      global $wpdb;
      if(isset($_GET['action']) && $_GET['action']=="approvecomment" && tmg_msma_check_referer()) {
        wp_set_comment_status($id, 'approve');
      } elseif (isset($_GET['action']) && $_GET['action']=="deletecomment" && tmg_msma_check_referer()) {
        wp_set_comment_status($id, 'delete');
      } elseif (isset($_GET['action']) && $_GET['action']=="spamcomment" && tmg_msma_check_referer()) {
        wp_set_comment_status($id, 'spam');
      } else {
    		$comment = $wpdb->get_results("SELECT $wpdb->comments.*, $wpdb->posts.post_title FROM $wpdb->comments INNER JOIN $wpdb->posts ON $wpdb->comments.comment_post_id = $wpdb->posts.id WHERE comment_ID=$id;" );
      }
    }
    if(!@$comment) {
      return tmg_msma_edit_comments();
    }
    tmg_msma_edit_comment($comment[0], true);
  }

  function tmg_msma_themes() {
   	if (isset($_POST['tmg_switcher_mode'])) {
      if (!tmg_msma_check_referer()) { return; }
      update_option('tmg_switcher_mode', $_POST['tmg_switcher_mode']);
      print "<p>" . __("Your changes have been applied.", 'tmg') . "</p>";
      print "<p><a href='/wp-admin/'>" . __("Continue.", 'tmg') . "</a></p>";
      return;
    }
    print '<form name="post" action="' . $_SERVER['REQUEST_URI'] . '" method="post" id="post">';
    print '<p><label for="title">' . __('Change the mobile switcher mode:', 'tmg') . '</label><br />';
    $current = get_option('tmg_switcher_mode');
    foreach(array(
      'none'=>__('Disabled', 'tmg'),
      'browser'=>__('Browser detection', 'tmg'),
      'domain'=>__('Domain mapping', 'tmg'),
      'browserdomain'=>__('BOTH: browser detection and domain mapping', 'tmg'),
    ) as $value=>$title) {
      print "<input style='width:32px;' type='radio' name='tmg_switcher_mode' value='$value'";
      if ($value == $current) {
        print " checked";
      }
      print "/> $title<br />";
    }
    print '</select></p>';
    print '<input class="button" name="submit" type="submit" id="submit" value="' . __('Apply', 'tmg') . '" />';
    print '</form>';
    print "<p>" . __("NB: Changing the switcher mode may return you to the desktop version of the admin pages. Be cautious if you are using a mobile device.", 'tmg') . "</p>";
  }

  function tmg_msma_edit_comment(&$comment, $full = false) {
    $base = get_option('home');
    $id = $comment->comment_ID;
    $content = strip_tags($comment->comment_content);
    $title = strip_tags($comment->comment_author);
    if(!$full) {
      $title = "<a href='$base/wp-admin/comment.php?action=editcomment&amp;c=$id'>$title</a>";
      if(strlen($content)>100) {
        $content = substr($content, 0, 100) . "...";
      }
    }
    $approve = "<a href='comment.php?action=approvecomment&amp;c=$id'>" . __('Approve', 'tmg') . "</a>";
    $delete = "<a href='comment.php?action=deletecomment&amp;c=$id'>" . __('Delete', 'tmg') . "</a>";
    $spam = "<a href='comment.php?action=spamcomment&amp;c=$id'>" . __('Spam', 'tmg') . "</a>";
    print "<p>" . sprintf(_c('<strong>%1$s</strong> on %2$s|comment_title ON post_title', 'tmg'), $title, $comment->post_title) .
      "<br />$content" .
      "<br />$approve | $delete | $spam" .
      "</p>";
  }

  function tmg_msma_options_general() {
    if(isset($_GET['option']) && is_numeric($id = $_GET['option'])) {
      if (sizeof($_POST) > 0) {
        if (!tmg_msma_check_referer()) { return; }
        tmg_msma_option_update($_POST);
        print "<p>" . __("Your changes have been applied.", 'tmg') . "</p>";
        return tmg_msma_options_list();
      } else {
        return tmg_msma_option_edit_form($id);
      }
    }
    tmg_msma_options_list();
  }

  function tmg_msma_options_filter() {
    return "WHERE option_name!='' AND " .
          "LEFT(option_name, 4)!='rss_' AND " .
          "NOT INSTR(option_name, 'widget') AND " .
          "NOT INSTR(option_name, 'plugin') AND " .
          "option_name NOT IN ('cron', 'update_core', 'recently_edited', 'wp_user_roles', 'category_children', 'tmg_deviceatlas_json_location')" .
          "";
  }

  function tmg_msma_options_list() {
  	global $wpdb;
    $base = get_option('home');
		$count = $wpdb->get_results("SELECT count(*) as cnt FROM $wpdb->options " . tmg_msma_options_filter());
		$count = ($count[0]->cnt);
    $size = 10;
    $page = 0;
    if(isset($_GET['page']) && is_numeric($_GET['page'])) {
      $page = $_GET['page'];
    }
    $start = $page * $size;
    $options = $wpdb->get_results("SELECT * FROM $wpdb->options " . tmg_msma_options_filter() . " order by option_id asc LIMIT $start, $size" );
    foreach($options as $option) {
      $editable = false;
      $label = tmg_msma_option_name($option->option_name);
      $value = tmg_msma_option_value($option->option_name, $option->option_value, $editable);
      if ($editable) {
        $label = "<a href='$base/wp-admin/options-general.php?page=$page&amp;option=$option->option_id'>$label</a>";
      }
      print "<p>$label: " . htmlentities($value) . "</p>";
    }
    $next = "";
    $previous = "";
    if($page>0) {
      $previous = "<a href='?page=" . ($page-1) . "'>" . __('Previous page', 'tmg') . "</a>";
    }
    if(($page+1) * $size < $count) {
      $next = "<a href='?page=" . ($page+1) . "'>" . __('Next page', 'tmg') . "</a>";
    }
    if ($next || $previous) {
      print "<p>$previous";
      if ($next && $previous) {
        print " | ";
      }
      print "$next</p>";
    }
    print "<p>" . __("NB: Some complex options cannot be edited in this mobile interface.", 'tmg') . "</p>";

  }
  function tmg_msma_option_edit_form($id) {
    global $wpdb;
    $option = $wpdb->get_results("SELECT * FROM $wpdb->options " . tmg_msma_options_filter() . " and option_id=$id");
    if(sizeof($option)==0) {
      print "<p>" . __("That option is not editable.", 'tmg') . "</p>";
      return tmg_msma_options_list();
    }
    $option = $option[0];
    $value = tmg_msma_option_value($option->option_name, $option->option_value, $editable);
    if(!$editable) {
      print "<p>" . __("That option is not editable.", 'tmg') . "</p>";
      return tmg_msma_options_list();
    }
    print '<form name="post" action="' . $_SERVER['REQUEST_URI'] . '" method="post" id="post">';
    print '<input type="hidden" name="option_name" value="' . attribute_escape($option->option_name) . '" />';

    print '<p><label for="title">' . tmg_msma_option_name($option->option_name) . ':</label><br />';
    print '<input type="text" name="option_value" value="' . attribute_escape($value) . '" id="title" /></p>';

    print '<input class="button" name="submit" type="submit" id="submit" value="' . __('Apply', 'tmg') . '" />';
    if($value==='0' or $value==='1') {
      print '<p>' . __('For options that are usually a checkbox, use 1 for \'on\', and 0 for \'off\'', 'tmg') . '</p>';
    }
    print '</form>';  }

  function tmg_msma_option_update($option) {
    if(isset($option['option_name'])) {
      update_option($option['option_name'], stripslashes($option['option_value']));
    }
  }

  function tmg_msma_option_name($name) {
    $name = str_replace("_", " ", $name);
    $name = strtoupper($name[0]) . substr($name, 1);
    if(substr($name, 0, 5)=='tmg ') {
      $name = "Mobile " . substr($name, 5);
    }
    $name = str_replace("Mobile deviceatlas", "DeviceAtlas", $name);
    $name = str_replace("Siteurl", "Site url", $name);
    $name = str_replace("Blogname", "Blog name", $name);
    $name = str_replace("Blogdescription", "Blog description", $name);
    $name = str_replace("Gzipcompression", "GZIP compression", $name);
    $name = str_replace("linksupdate", "links update", $name);
    $name = str_replace("yearmonth", "year/month", $name);
    $name = str_replace(" url", " URL", $name);
    $name = str_replace(" uri", " URI", $name);
    $name = str_replace("Gmt", "GMT", $name);
    $name = str_replace("Html", "HTML", $name);
    $name = str_replace("rss", "RSS", $name);
    return $name;
  }
  function tmg_msma_option_value($name, $value, &$editable) {
    $value = maybe_unserialize($value);
    if (gettype($value)=='object') { //is_object has incomplete class bug
      $value = "(locked)";
    } elseif (is_array($value)) {
      $value = "(locked)";
    } else {
      $editable = true;
    }
    return print_r($value, 1);
  }

  function tmg_msma_get_pagenum_link($link) {
    return str_replace('&amp;post=', '&amp;_post=',
           str_replace('&post=', '&_post=', $link)); // remove post-post-POST evidence
  }

  function tmg_msma_post_status($status) {
    switch($status) {
      case 'publish':
      case 'private':
        return __('Published', 'tmg');
      case 'future':
        return __('Scheduled', 'tmg');
      case 'pending':
        return __('Pending Review', 'tmg');
      default:
        return __('Unpublished', 'tmg');
    }
  }


  function tmg_msma_check_referer() {
    $base = get_option('home');
    $admin = "$base/wp-admin";
    $referer = $_SERVER['HTTP_REFERER'];
    if (substr($referer, 0, strlen($admin)) != $admin) {
      print __("You may only originate this action from the admin pages", 'tmg');
      return false;
    }
    return true;
  }


?>
