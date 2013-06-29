<div class="wrap">
  <h2>
    <?php _e('Mobile Switcher', 'tmg') ?>
    <p style='font-size:small;font-style:italic;margin:0'>
      <?php _e('Part of the WordPress Mobile Pack', 'tmg'); ?>
    </p>
  </h2>
  <form method="post" action="">
    <table class="form-table">
      <tr class='tmg_theme'>
        <th><?php _e('Domain name', 'tmg'); ?></th>
        <th><?php _e('Home category', 'tmg'); ?></th>
        <th>
          <?php _e('HTML theme', 'tmg'); ?>
          <br />
          <?php _e('Default', 'tmg').':'; ?>
          <a href='/wp-admin/themes.php' target='_blank'><?php print tmg_switcher_desktop_theme(); ?></a>
        </th>
        <th>
          <?php _e('Mobile theme', 'tmg'); ?>
          <br />
          <?php _e('Default', 'tmg').':'; ?>
          <a href='/wp-admin/themes.php' target='_blank'><?php print tmg_switcher_desktop_theme(); ?></a>
        </th>
      </tr>
      <?php foreach (tmg_switcher_domain_list() as $key => $value):?>
      <?php
        $key = str_replace('.', '_', $key);
        $option = 'tmg_switcher_'.$key;
      ?>
      <tr class='tmg_theme'>
        <td><?php print $value ?></td>
        <td>
          <?php print tmg_switcher_option_category($option.'_category_home'); ?>
        </td>
        <td>
          <?php print tmg_switcher_option_themes($option.'_html_theme'); ?>
        </td>
        <td>
          <?php print tmg_switcher_option_themes($option.'_mobile_theme'); ?>
        </td>
      </tr>
      <?php endforeach ?>
      
    </table>
    <p class="submit">
      <input type="submit" name="Submit" value="<?php _e('Save Changes', 'tmg'); ?>" />
    </p>
  </form>
</div>

<script>
  var tmg_pale = 0.3;
  var tmg_speed = 'slow';
  function tmgSwitcherMode(speed) {
    if (speed==null) {speed=tmg_speed;}
    var value = jQuery("#tmg_switcher_mode").val();
    var browser = value.indexOf("browser")>-1;
    var domain = value.indexOf("domain")>-1;
    jQuery(".tmg_browser").children().fadeTo(speed, browser ? 1 : tmg_pale);
    jQuery(".tmg_desktop_domain").children().fadeTo(speed, (domain||browser) ? 1 : tmg_pale);
    jQuery(".tmg_mobile_domain").children().fadeTo(speed, domain ? 1 : tmg_pale);
    jQuery(".tmg_theme").children().fadeTo(speed, (domain||browser) ? 1 : tmg_pale);
    jQuery(".tmg_links").children().fadeTo(speed, (domain||browser) ? 1 : tmg_pale);
  }
  tmgSwitcherMode(-1);
</script>
