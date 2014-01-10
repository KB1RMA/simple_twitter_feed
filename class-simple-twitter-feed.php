<?php
/**
 * Simple Twitter Feed
 *
 * @package   Simple_Twitter_Feed
 * @author    Chris Snyder	<chris@keyedupmedia.com>
 * @license   GPL-2.0+
 * @link      http://keyedupmedia.com
 * @copyright 2013 Keyed-Up Media, LLC
 */

/**
 * Simple Twitter Feed
 *
 * @package Simple_Twitter_Feed
 * @author  Chris Snyder <chris@keyedupmedia.com>
 */
class Simple_Twitter_Feed {

	/**
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	public $consumer_key;
	public $consumer_secret;
	public $oauth_token;
	public $oauth_token_secret;
	public $username;

	/**
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '1.0.0';

	/**
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'simple-twitter-feed';

	/**
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected $connection = null;

	/**
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected $account = null;

	/**
	 * @since    1.0.0
	 *
	 * @var      int
	 */
	protected $ttl;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Add the option page
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Register admin styles and scripts
		#add_action( 'admin_print_styles', array( $this, 'register_admin_styles' ) );
		#add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

		// Register site styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Register Widgets
		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		// Register AJAX function for callback
		add_action( 'wp_ajax_simpletwitter_auth', array( $this, 'authorize_with_twitter' ) );

		// Initialize options from wordpress
		$this->consumer_key = get_option('simpletweetfeed_consumer_key');
		$this->consumer_secret = get_option('simpletweetfeed_consumer_secret');
		$this->oauth_token = get_option('simpletweetfeed_oauth_token');
		$this->oauth_token_secret = get_option('simpletweetfeed_oauth_token_secret');
		$this->username = get_option('simpletweetfeed_username');
		$this->ttl = get_option('simpletweetfeed_ttl');
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting('simpletweetfeed_settings', 'simpletweetfeed_ttl');
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
	}

	/**
	 * Register and enqueue public-facing scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
	}

	/**
	 * Registers widgets
	 *
	 * @since    1.0.0
	 */
	public function register_widgets() {
		register_widget('Simple_Feed_Widget');
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		$this->plugin_screen_hook_suffix = add_options_page(
			__( 'Simple Twitter Feed', $this->plugin_slug ),
			__( 'Twitter Options', $this->plugin_slug ),
			'read',
			$this->plugin_slug,
			array( $this, 'display_plugin_admin_page' )
		);
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		if (!empty($_GET['callback']))
			$this->twitter_callback();

		include_once( 'views/admin.php' );
	}

	/**
	 * Checks to see if we're authenticated to twitter
	 */
	public function is_authenticated() {
		if (!$this->connection)
			$this->connect();

		if (!$this->account)
			$this->account = $this->connection->get('account/verify_credentials');

		if (!empty($this->account->errors)) {
			return false;
		}

		update_option( 'simpletweetfeed_username', $this->account->screen_name );
		$this->username = $this->account->screen_name;

		return true;
	}

	/**
	 * Outputs error messages
	 */
	public function display_errors() {
		if (!empty($this->account->errors)) {
			foreach ($this->account->errors as $error) {
				echo '<div class="error">'. $error->message .'</div>';
			}
		} else {
			return false;
		}
	}

	/**
	 * AJAX Handler to redirect the user to the twitter authorization URL
	 *
	 */
	public function authorize_with_twitter() {
		if(!is_user_logged_in())
        return false;

		if (!empty($_POST['simpletweetfeed_consumer_key']))
			update_option( 'simpletweetfeed_consumer_key', $_POST['simpletweetfeed_consumer_key'] );

		if (!empty($_POST['simpletweetfeed_consumer_secret']))
			update_option( 'simpletweetfeed_consumer_secret', $_POST['simpletweetfeed_consumer_secret'] );

		$consumer_key = get_option('simpletweetfeed_consumer_key');
		$consumer_secret = get_option('simpletweetfeed_consumer_secret');

		$connection = new TwitterOAuth(get_option('simpletweetfeed_consumer_key'), get_option('simpletweetfeed_consumer_secret'));
		$temporary_credentials = $connection->getRequestToken(admin_url('options-general.php?page=simple-twitter-feed&callback=true'));

		$_SESSION['oauth_token'] = $temporary_credentials['oauth_token'];
		$_SESSION['oauth_token_secret'] = $temporary_credentials['oauth_token_secret'];

		$redirect = $connection->getAuthorizeURL($temporary_credentials['oauth_token'], FALSE);

		echo json_encode(compact('redirect'));
		exit;
	}

	/*--------------------------------------------------*/
	/* Private Functions
	/*--------------------------------------------------*/

	/**
	 * Method to handle the Twitter callback.
	 *
	 */
	private function twitter_callback() {
		if (isset($_SESSION['oauth_stale']))
			return;

		$connection = new TwitterOAuth(
			$this->consumer_key,
			$this->consumer_secret,
			$_SESSION['oauth_token'],
			$_SESSION['oauth_token_secret']
		);

		$token_credentials = $connection->getAccessToken($_REQUEST['oauth_verifier']);

		update_option( 'simpletweetfeed_oauth_token', $token_credentials['oauth_token'] );
		update_option( 'simpletweetfeed_oauth_token_secret', $token_credentials['oauth_token_secret'] );

		$this->oauth_token = $token_credentials['oauth_token'];
		$this->oauth_token_secret = $token_credentials['oauth_token_secret'];

		if ( $this->is_authenticated() ) {
			unset($_SESSION['oauth_token']);
			unset($_SESSION['oauth_token_secret']);
			$_SESSION['oauth_stale'] = true;
		}
	}

	/**
	 * Connects and authenticates to twitter
	 */
	private function connect() {
		$this->connection = new TwitterOAuth(
			$this->consumer_key,
			$this->consumer_secret,
			$this->oauth_token,
			$this->oauth_token_secret
		);
	}



}