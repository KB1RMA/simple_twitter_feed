<?php

/**
 * Start Main Widget
 * WP_Twitter_Widget
 *
 * @since 3.2.8
 */
class Simple_Feed_Widget extends WP_Widget {
	public $consumer_key;
	public $consumer_secret;
	public $oauth_token;
	public $oauth_token_secret;
	public $username;

	/**
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $connection = null;

	/**
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '1.0.0';

	/**
	 * @since   1.0.0
	 *
	 * @var     int
	 */
	protected $ttl;

	/*--------------------------------------------------*/
	/* Constructor
	/*--------------------------------------------------*/

	/**
	 * Specifies the classname and description, instantiates the widget,
	 * loads localization files, and includes necessary stylesheets and JavaScript.
	 */
	public function __construct() {
		parent::__construct(
			'simple-feed-widget',
			__('Simple Twitter Feed'),
			array(
				'classname' => 'simple-feed-widget',
				'description' => __('')
			),
			array(
				'width' => 400,
				'height' => 350
			)
		);

		// Initialize options from wordpress
		$this->consumer_key = get_option('simpletweetfeed_consumer_key');
		$this->consumer_secret = get_option('simpletweetfeed_consumer_secret');
		$this->oauth_token = get_option('simpletweetfeed_oauth_token');
		$this->oauth_token_secret = get_option('simpletweetfeed_oauth_token_secret');
		$this->username = get_option('simpletweetfeed_username');
		$this->ttl = get_option('simpletweetfeed_ttl');
	}

	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/

	/**
	 * Outputs the content of the widget.
	 *
	 * @param	array	args		The array of form elements
	 * @param	array	instance	The current instance of the widget
	 */
	public function widget($args, $instance) {
		extract($args);
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
		$title = preg_replace('/@(\w+)/','<a target="_blank" class="statuslink" href="https://twitter.com/$1">@$1</a>', $title);
		$username = $instance['username'];
		if(empty($username)) { $username = $this->username; }
		$count = absint($instance['count']);
		$timeago = $instance['timeago'] ? 1 : 0;

		wp_enqueue_style( 'simple-twitter-feed-plugin-styles', plugins_url( 'simple-twitter-feed/css/public.css'), array(), $this->version );

		if($this->is_authenticated()) {
			$content = $this->retrieve_tweets($username, $count);
		} else {
			$content = null;
		}

		include( plugin_dir_path( __FILE__ ) . '/views/widget.php' );
	}

	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param	array	new_instance	The previous instance of values before the update.
	 * @param	array	old_instance	The new instance of values to be generated via the update.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['username'] = strip_tags($new_instance['username']);
		$instance['count'] = absint( $new_instance['count'] );
		$instance['showheader'] = $new_instance['showheader'] ? 1 : 0;
		$instance['timeago'] = $new_instance['timeago'] ? 1 : 0;
		$instance['showdesc'] = $new_instance['showdesc'] ? 1 : 0;

		return $instance;
	}

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param	array	instance	The array of keys and values for the widget.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title' => 'Latest Tweets',
				'username' => $this->username,
				'count' => 10,
				'showheader' => 1,
				'timeago' => 1
			)
		);

		$title = strip_tags($instance['title']);
		$username = strip_tags($instance['username']);
		$count = isset($instance['count']) ? absint($instance['count']) : 5;
		$showheader = $instance['showheader'] ? 'checked="checked"' : '';
		$timeago = $instance['timeago'] ? 'checked="checked"' : '';
		$showdesc = $instance['showdesc'] ? 'checked="checked"' : '';

		// Display the admin form
		include( plugin_dir_path(__FILE__) . '/views/form.php' );
	}

	/*--------------------------------------------------*/
	/* Public Methods
	/*--------------------------------------------------*/

	/**
	 * Loads the Widget's text domain for localization and translation.
	 */
	public function widget_textdomain() {
		load_plugin_textdomain( 'simple-feed-widget-locale', false, plugin_dir_path( __FILE__ ) . '/lang/' );
	}

	/**
	 * Processes Hash tags, links, and at replies in tweets
	 *
	 * @param	string	tweet	The tweet that needs to be cleaned
	 */
	public function clean_tweet($tweet) {
		$regexps = array (
			"link"  => '/[a-z]+:\/\/[a-z0-9-_]+\.[a-z0-9-_@:~%&\?\+#\/.=]+[^:\.,\)\s*$]/i',
			"at"    => '/(^|[^\w]+)\@([a-zA-Z0-9_]{1,15}(\/[a-zA-Z0-9-_]+)*)/',
			"hash"  => "/(^|[^&\w'\"]+)\#([a-zA-Z0-9_]+)/"
		);

		foreach ($regexps as $name => $re)
			$tweet = preg_replace_callback($re, array( $this, 'parse_tweet_'.$name), $tweet);

		return $tweet;
	}

	public function parse_tweet_link($m) {
		return '<a target="_blank" href="'.$m[0].'">'.((strlen($m[0]) > 25) ? substr($m[0], 0, 24).'...' : $m[0]).'</a>';
	}

	public function parse_tweet_at($m) {
		return $m[1].'@<a target="_blank" href="http://twitter.com/'.$m[2].'">'.$m[2].'</a>';
	}

	public function parse_tweet_hash($m) {
		return $m[1].'#<a target="_blank" href="http://search.twitter.com/search?q=%23'.$m[2].'">'.$m[2].'</a>';
	}

	/**
	 * Checks to see if we're authenticated to twitter
	 */
	public function is_authenticated() {
		if(empty($this->oauth_token) && empty($this->oauth_token_secret))
			return false;

		if(empty($this->consumer_key) && empty($this->consumer_secret))
			return false;

		$account = get_transient('simpletwitterfeed-account-'. $this->username);
		if (!$account) {
			if (!$this->connection)
				$this->connect();

			$account = $this->connection->get('account/verify_credentials');
		}

		if (!empty($account->errors))
			return false;

		set_transient('simpletwitterfeed-account-'. $account->screen_name, $account, $this->ttl);

		return true;
	}

	/*--------------------------------------------------*/
	/* Private Methods
	/*--------------------------------------------------*/

	/**
	 * Connects and authenticates to twitter
	 */
	private function connect() {
		$hash = md5($this->consumer_key . $this->consumer_secret . $this->oauth_token . $this->oauth_token_secret);

		$this->connection = get_transient($hash);

		if (!$this->connection) {
			$this->connection = new TwitterOAuth(
				$this->consumer_key,
				$this->consumer_secret,
				$this->oauth_token,
				$this->oauth_token_secret
			);

			if($this->is_authenticated()) {
				set_transient($hash, $this->connection, $this->ttl);
			}
		}
	}

	/**
	 * Grab tweets
	 *
	 * @param string username
	 * @param int count
	 */
	private function retrieve_tweets($username = null, $count = 10) {
		if (!$username)
			return false;

		$cacheKey = 'simpletweet-cache-'. $username .'-'. $count;

		$content = get_transient($cacheKey);
		if (!$content) {
			$this->connect();
			$content = $this->connection->get('statuses/user_timeline', array(
				'screen_name' => $username,
				'count' => $count
			));
			set_transient($cacheKey, $content, $this->ttl);
		}

		return $content;
	}

}