<div class="wrap">
  <h2>
    <?php _e('Mobile Analytics', 'tmg') ?>
    <p style='font-size:small;font-style:italic;margin:0'>
      <?php _e('Part of the WordPress Mobile Pack', 'tmg'); ?>
    </p>
  </h2>
  <form method="post" action="">
    <p>
      <?php _e('The Mobile Pack keeps a basic local tally of mobile traffic. However, we recommend you register with an external provider to obtain much richer statistics.', 'tmg'); ?>
    </p>
    <table class="form-table">
      <?php if (tmg_analytics_local_enabled()) { ?>
        <tr>
          <th><?php _e('Local analytics', 'tmg'); ?></th>
          <td>
            <?php print tmg_analytics_local_summary(); ?>
            <br />
            <?php print tmg_analytics_option('tmg_analytics_local_reset'); ?> <strong><?php _e("Reset counter", 'tmg'); ?></strong>
          </td>
        </tr>
      <?php } ?>
    </table>
    <p>
      <?php _e('Note that Percent Mobile\'s external analytics service is no longer available.', 'tmg'); ?>
    </p>
    <p class="submit">
      <input type="submit" name="Submit" value="<?php _e('Save Changes', 'tmg'); ?>" />
    </p>
  </form>
</div>

<script>
  var tmg_pale = 0.3;
  var tmg_speed = 'slow';
  function tmgProvider(speed) {
    if (speed==null) {speed=tmg_speed;}
    var value = jQuery("#tmg_analytics_provider").val();
    jQuery(".tmg_provider").children().fadeTo(speed, (value!='none') ? 1 : tmg_pale);
  }
  tmgProvider(-1);
</script>
