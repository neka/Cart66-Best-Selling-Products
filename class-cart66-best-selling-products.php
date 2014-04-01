<?php
/**
 * Cart66 Also Bought.
 *
 * @package   cart66BestSellingProducts
 * @author    Kane Andrews <hello@kaneandre.ws>
 * @license   GPL-2.0+
 * @link      http://kaneandre.ws
 * @copyright 2013 Kane Andrews
 */

class cart66BestSellingProducts {

	/**
	 * Plugin version.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	protected $version = '1.0.0';

	/**
	 * Unique identifier
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'cart66-best-selling-products';

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Slug of the plugin screen.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_screen_hook_suffix = null;

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Add the options page and menu item.
		//add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Load admin style sheet and JavaScript.
		//add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

		// Load public-facing style sheet and JavaScript.
		//add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

	}

	/**
	 * Return an instance of this class.
	 * Check if Cart66 is installed or not.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ((is_plugin_active('cart66-lite/cart66.php')) || (is_plugin_active('cart66/cart66.php'))) {
		// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;

		} else {

			function admin_notice_message(){    
				echo '<div class="updated"><p>You need the Cart66 plugin to use the <i>Cart66 Best Selling Products</i> addon.</p></div>';
			}

			add_action('admin_notices', 'admin_notice_message');

		}
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

	}

	

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since     1.0.0
	 *
	 * @return    null    Return early if no settings page is registered.
	 */
	public function enqueue_admin_styles() {

		if ( ! isset( $this->plugin_screen_hook_suffix ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( $screen->id == $this->plugin_screen_hook_suffix ) {
			wp_enqueue_style( $this->plugin_slug .'-admin-styles', plugins_url( 'css/admin.css', __FILE__ ), array(), $this->version );
		}

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/cart66-best-selling-products.css', __FILE__ ), array(), $this->version );
	}


}
/**
 * Main code.
 *
 * @since    1.0.0
 */
function best_selling_products() {

	global $post, $wpdb;
	//Get product ID from post content
	$pieces = explode("add_to_cart item=", $post->post_content);
	$more = explode("quantity", $pieces[1]);
	$item_number = number_format( str_replace('"', '', $more[0]) );

	//Find all order IDs that contain the product
	$orderids=$wpdb->get_col( $wpdb->prepare( "SELECT order_id FROM ".$wpdb->prefix."cart66_order_items WHERE item_number = %d", $item_number ) );

	//Create string of all order IDs
	foreach ( $orderids as $order ) 
	{
		$sum .= $order . ", ";
	}

	//Trim string for use
	$sum = substr($sum, 0, -2);

	//Find all unique items in the same orders
	$otheritems = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT item_number FROM ".$wpdb->prefix."cart66_order_items WHERE order_id IN (".$sum.")", $sum ) );	
	$stack = array();

	//Get info of each product from item number, including corresponding post. 
	foreach ( $otheritems as $item ) 
	{
		$stringtofind = 'add_to_cart item="'.$item;
		$thepost = $wpdb->get_row("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%%$stringtofind%%' AND post_status = 'publish'");
		if ($thepost) {
			array_push($stack, $thepost->ID);
		}
		unset($postit);
	}
	//Remove original item from array
	if(($key = array_search($post->ID, $stack)) !== false) {
		unset($stack[$key]);
	}

	//Get amount from settings
	$amount = get_option('also_bought');

	if ($amount > 0) {
		
		//Setup query
		$args=array(
			'post_type' => 'products',
			'post_status' => 'publish',
			'showposts' => $amount,
			'orderby'=> 'rand',
			'post__in' => $stack,
			);
		$amount_query = new WP_Query($args);

		//Run the loop
		while ($amount_query->have_posts()) : $amount_query->the_post(); 
		require( 'views/template.php' );
		endwhile;
		wp_reset_query();
	}
}