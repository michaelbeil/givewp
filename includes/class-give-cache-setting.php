<?php
/**
 * Class for managing plugin settings cache
 *
 * Note: only use for internal purpose.
 *
 * @package     Give
 * @subpackage  Classes/Give_Cache_Setting
 * @copyright   Copyright (c) 2018, GiveWP
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       2.4.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Give_Cache_Setting
 */
class Give_Cache_Setting {
	/**
	 * Instance.
	 *
	 * @since  2.4.0
	 * @access private
	 * @var Give_Cache_Setting
	 */
	static private $instance;

	/**
	 * Cache key.
	 *
	 * @since  2.4.0
	 * @access private
	 * @var string
	 */
	private $cache_key = 'giveAllOptions';


	/**
	 * Cache group.
	 *
	 * @since  2.4.0
	 * @access private
	 * @var string
	 */
	private $cache_group = 'give-options';

	/**
	 * Array of cached settings
	 *
	 * @since  2.4.0
	 * @access private
	 * @var array
	 */
	private $settings = array(
		'give_settings'           => array(),
		'give_version'            => '',
		'give_completed_upgrades' => array(),
		'currencies'              => array(),
		'gateways'                => array(),
	);

	/**
	 * Array of cached setting db option names
	 *
	 * @since  2.4.0
	 * @access private
	 * @var array
	 */
	private $db_option_ids = array(
		'give_settings',
		'give_version',
		'give_completed_upgrades',
	);

	/**
	 * Array of cached setting option names
	 *
	 * @since  2.4.0
	 * @access private
	 * @var array
	 */
	static private $all_option_ids;

	/**
	 * Singleton pattern.
	 *
	 * @since  2.4.0
	 * @access private
	 */
	private function __construct() {
	}


	/**
	 * Get instance.
	 *
	 * @since  2.4.0
	 * @access public
	 * @return Give_Cache_Setting
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			self::$instance = new static();

			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Setup
	 *
	 * @since  2.4.0
	 * @access private
	 */
	private function setup() {
		self::$all_option_ids = array_keys( $this->settings );

		$this->load_plugin_settings();

		add_action( 'added_option', array( $this, '__reload_plugin_settings' ) );
		add_action( 'updated_option', array( $this, '__reload_plugin_settings' ) );
		add_action( 'deleted_option', array( $this, '__reload_plugin_settings' ) );

		add_action( 'give_init', array( $this, '__setup_currencies_list' ), 11 );
		add_action( 'give_init', array( $this, '__setup_gateways_list' ), 11 );
	}

	/**
	 * Load plugin settings
	 *
	 * @since  2.4.0
	 * @access private
	 */
	private function load_plugin_settings() {
		global $wpdb;

		$cache = wp_cache_get( $this->cache_key, $this->cache_group );

		// Load options from cache.
		if ( false !== $cache ) {
			$this->settings = $cache;

			return;
		}

		$db_option_ids = '\'' . implode( '\',\'', $this->db_option_ids ) . '\'';

		$tmp     = array();
		$sql     = "SELECT option_name, option_value FROM $wpdb->options WHERE option_name IN ({$db_option_ids}) ";
		$results = $wpdb->get_results( $sql );

		if ( ! empty( $results ) ) {

			/* @var  stdClass $result */
			foreach ( $results as $result ) {
				$this->settings[ $result->option_name ] = maybe_unserialize( $result->option_value );
			}

			wp_cache_set( $this->cache_key, $tmp, $this->cache_group );
		}
	}

	/**
	 * Reload option when add, update or delete
	 * 
	 * Note: only for internal logic
	 *
	 * @since 2.4.0
	 *
	 * @param $option_name
	 */
	public function __reload_plugin_settings( $option_name ) {
		// Bailout.
		if ( ! in_array( $option_name, $this->db_option_ids ) ) {
			return;
		}

		wp_cache_delete( $this->cache_key, $this->cache_group );
		$this->load_plugin_settings();
	}

	/**
	 * Setup currencies list
	 *
	 * @since 2.4.0
	 */
	public function __setup_currencies_list() {
		$currencies = require_once GIVE_PLUGIN_DIR . 'includes/currencies-list.php';

		/**
		 * Filter the supported currency list
		 *
		 * @since 2.4.0
		 */
		$currencies = apply_filters( 'give_register_currency', $currencies );

		$this->settings['currencies'] = $currencies;
	}


	/**
	 * Setup gateway list
	 *
	 * @since 2.4.0
	 */
	public function __setup_gateways_list() {
		// Default, built-in gateways
		$gateways = array(
			'paypal'  => array(
				'admin_label'    => __( 'PayPal Standard', 'give' ),
				'checkout_label' => __( 'PayPal', 'give' ),
			),
			'manual'  => array(
				'admin_label'    => __( 'Test Donation', 'give' ),
				'checkout_label' => __( 'Test Donation', 'give' ),
			),
			'offline' => array(
				'admin_label'    => esc_attr__( 'Offline Donation', 'give' ),
				'checkout_label' => esc_attr__( 'Offline Donation', 'give' ),
			),
		);

		/**
		 * Filter the supported gateways list
		 *
		 * @since 2.4.0
		 */
		$gateways = apply_filters( 'give_register_gateway', $gateways );

		$this->settings['gateways'] = $gateways;
	}


	/**
	 * Get option
	 *
	 * @since  2.4.0
	 * @access public
	 *
	 * @param      $option_name
	 * @param bool $default
	 *
	 * @return mixed
	 */
	public static function get_option( $option_name, $default = false ) {
		$value = $default;

		if ( in_array( $option_name, self::$all_option_ids ) ) {
			$value = ! empty( self::$instance->settings[ $option_name ] )
				? self::$instance->settings[ $option_name ]
				: $default;
		}

		return $value;
	}

	/**
	 * Get plugin settings
	 *
	 * @since  2.4.0
	 * @access public
	 */
	public static function get_settings() {

		/**
		 * Filter the plugin setting
		 */
		return (array) apply_filters( 'give_get_settings', self::$instance->settings['give_settings'] );
	}
}

Give_Cache_Setting::get_instance();
