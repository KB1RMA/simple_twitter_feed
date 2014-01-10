<?php
	if ( !current_user_can( 'manage_options' ) )
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
?>
<div class="wrap">
	<h2>Simple Twitter Feed Options</h2>
	<?php if (!$this->is_authenticated()) : ?>
	<?php endif ?>
	<form method="post" action="options.php">
		<?php settings_fields( 'simpletweetfeed_settings' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">Consumer Key</th>
				<td><input id="consumer_key" type="text" name="simpletweetfeed_consumer_key" value="<?php echo get_option('simpletweetfeed_consumer_key'); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Consumer Secret</th>
				<td><input id="consumer_secret" type="text" name="simpletweetfeed_consumer_secret" value="<?php echo get_option('simpletweetfeed_consumer_secret'); ?>" /></td>
			</tr>
			<tr valign="top">
				<td></td>
				<td>
				<?php if ($this->is_authenticated()) : ?>
					<p class="connected">You are connected to twitter!</p>
				<?php else : ?>
					<?php $this->display_errors() ?>
				<?php endif ?>
					<button<?php if ($this->is_authenticated()) : ?> disabled<?php endif ?> id="connect-to-twitter" name="connect" value="connect" class="button button-primary">Connect With Twitter</button>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Cache TTL</th>
				<td><input type="text" name="simpletweetfeed_ttl" value="<?php echo get_option('simpletweetfeed_ttl'); ?>" /></td>
			</tr>
		</table>
		<?php submit_button(); ?>

	</form>
</div>

<script>
(function (window, $) {
	$(function () {
		$('#connect-to-twitter').click(function () {
			var
				_wpnonce = $('#_wpnoonce').val(),
				key = $('#consumer_key').val(),
				secret = $('#consumer_secret').val();

			$.ajax({
				type: "POST",
				url : '<?php echo admin_url('admin-ajax.php') ?>',
				dataType: 'json',
				data : {
					action: 'simpletwitter_auth',
					simpletweetfeed_consumer_key: key,
					simpletweetfeed_consumer_secret: secret
				}
			}).done(function (data, textStatus) {
				console.log(data.redirect);
				if (data.redirect)
					window.location.href = data.redirect;

			}).fail(function (xhr) {
				console.log(xhr.getResponseHeader());
			});

			return false;
		});

	});
}(this, jQuery));
</script>