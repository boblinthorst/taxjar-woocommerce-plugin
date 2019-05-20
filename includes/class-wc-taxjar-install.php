<?php
/**
 * Installation related functions and actions.
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Taxjar_Install Class.
 */
class WC_Taxjar_Install {

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
	}

	/**
	 * Check TaxJar version and run the installer if required
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'taxjar_version' ), WC_Taxjar::$version, '<' ) ) {
			self::install();
			do_action( 'taxjar_updated' );
		}
	}

	/**
	 * Install TaxJar
	 */
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		// Check if we are not already running this routine.
		if ( 'yes' === get_transient( 'taxjar_installing' ) ) {
			return;
		}

		// If we made it till here nothing is running yet, lets set the transient now.
		set_transient( 'taxjar_installing', 'yes', MINUTE_IN_SECONDS * 10 );
		wc_maybe_define_constant( 'TAXJAR_INSTALLING', true );

		self::create_tables();
		self::update_taxjar_version();

		delete_transient( 'taxjar_installing' );

		do_action( 'taxjar_installed' );
	}

	/**
	 * Update TaxJar version to current.
	 */
	private static function update_taxjar_version() {
		delete_option( 'taxjar_version' );
		add_option( 'taxjar_version', WC_Taxjar::$version );
	}

	/**
	 * Set up the database tables
	 *
	 * Tables:
	 *      taxjar_record_queue - Table for storing records to be synced to TaxJar
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$results = dbDelta( self::get_schema() );
		$test = 'test';
	}

	/**
	 * Get Table schema.
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
CREATE TABLE {$wpdb->prefix}taxjar_record_queue (
  queue_id BIGINT UNSIGNED NOT NULL auto_increment,
  record_id BIGINT UNSIGNED NOT NULL,
  record_type varchar(200) NOT NULL,
  record_data longtext NOT NULL,
  status varchar(200) NOT NULL DEFAULT 'new',
  batch_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_datetime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  processed_datetime datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  retry_count smallint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY  (queue_id)
  ) $collate;
		";

		return $tables;
	}

	/**
	 * Return a list of TaxJar tables. Used to make sure all TaxJar tables are dropped when uninstalling the plugin or
	 * uninstalling WooCommerce
	 *
	 * @return array TaxJar tables.
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}taxjar_record_queue",
		);

		return $tables;
	}

	/**
	 * Drop TaxJar tables.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @param array $tables List of tables that will be deleted by WP.
	 *
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		return array_merge( $tables, self::get_tables() );
	}
}

WC_Taxjar_Install::init();
