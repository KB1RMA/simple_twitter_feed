<?php ?>
<p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id('username'); ?>"><?php _e('Twitter username:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('username'); ?>" name="<?php echo $this->get_field_name('username'); ?>" type="text" value="<?php echo esc_attr($username); ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Number of tweets:'); ?></label>
	<input id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" size="3" />
</p>
<p>
	<input class="checkbox" type="checkbox" <?php echo $timeago; ?> id="<?php echo $this->get_field_id('timeago'); ?>" name="<?php echo $this->get_field_name('timeago'); ?>" value="1"/>
	<label for="<?php echo $this->get_field_id('timeago'); ?>"><?php _e('Human readable time format'); ?></label>
</p>