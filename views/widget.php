<?php $title = empty($title) ? '&nbsp;' : apply_filters('widget_title', $title); ?>
<?php echo $before_widget ?>
<div class="latest-tweet">
<?php if( !empty( $title ) && $title != "&nbsp;") : ?>
	<?php echo $before_title . $title . $after_title; ?>
<?php endif ?>
<?php if(!$content) : ?>
	<div class="latest-twitter-tweet">Please authenticate with Twitter.</div>
<?php elseif(count($content) > 0) : ?>
	<?php foreach($content as $key=>$tweet ) : ?>
	<div class="latest-twitter-tweet">&quot;<?php echo $this->clean_tweet($tweet->text) ?>&quot;</div>
	<?php endforeach ?>
<?php else : ?>
	<div class="latest-twitter-tweet">No Tweets</div>
<?php endif; ?>

	<div id="latest-twitter-follow-link">
		<a href="https://twitter.com/<?php echo $username ?>">follow me! &#9658;</a>
	</div>
</div>
<?php echo $after_widget ?>