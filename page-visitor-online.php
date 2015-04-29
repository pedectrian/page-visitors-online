<?php
/**
 * Plugin Name: Page visitors online.
 * Version: 0.0.1
 * Author: Alexander Permyakov
 * Author URI: http://ready2dev.ru
 * License: GPL2
 */
namespace Pedectrian;

class PageVisitorsOnline
{
	const DB_VERSION = '0.0.1';
	const VISITORS_ONLINE_DB = 'page_visitors_online';
	const VISITORS_DAILY_DB = 'page_visitors_daily';

	public $table_name;

	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array($this, 'install') );
	}

	public function init()
	{
		add_shortcode( 'page_visitors_online', array( $this, 'pageVisitorsOnlineShortcode') );
		$user = isset($_COOKIE['pvo_hash']) ? $_COOKIE['pvo_hash'] : null;

		if (!$user) {
			$user = uniqid() . uniqid();

			setcookie('pvo_hash', $user, time()+3600*24*100);
		}

		if ( $user ) {
			self::cleanOldLookers($user);

//			if ( defined( 'DOING_AJAX' ) && !DOING_AJAX )
//			{
				self::lookAtPage($user);
//			}

		}
	}

	/**
	 * Removes user's history and old (> 3min) page views
	 * @param string $user
	 */
	public static function cleanOldLookers($user)
	{
		global $wpdb;
		$now = new \DateTime('now -3 minutes');
		$yesterday = new \DateTime('now');
		$yesterday->setTime(0, 0, 0);
		$onlineVisitorsTable = $wpdb->prefix . self::VISITORS_ONLINE_DB;
		$dailyVisitorsTable = $wpdb->prefix . self::VISITORS_DAILY_DB;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$onlineVisitorsTable}
				 	WHERE user_hash = %s OR
				 	visit_date < '{$now->format('Y-m-d H:i:s')}'
				",
				$user
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$dailyVisitorsTable}
				 	WHERE visit_date < '{$yesterday->format('Y-m-d H:i:s')}'
				",
				$user
			)
		);
	}

	/**
	 * Adds an online visitor and daily view
	 * @param string $user
	 */
	public static function lookAtPage($user)
	{
		global $wpdb;
		$onlineVisitorsTable = $wpdb->prefix . self::VISITORS_ONLINE_DB;
		$dailyVisitorsTable = $wpdb->prefix . self::VISITORS_DAILY_DB;

		$page = $_SERVER['REQUEST_URI'];
		print_r($_SERVER);

		$wpdb->insert($onlineVisitorsTable , array(
				'visit_date' => date('Y-m-d H:i:s'),
				'page_id' => $page,
				'user_hash' => $user
			)
		);

		$wpdb->insert($dailyVisitorsTable , array(
				'visit_date' => date('Y-m-d H:i:s'),
				'page_id' => $page,
				'user_hash' => $user
			)
		);
	}

	/**
	 * Creates page_visitors_online table
	 */
	public function install()
	{
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		self::installVisitorsOnlineTable();
		self::installVisitorsDailyTable();

		add_option( 'pvo_db_version', PageVisitorsOnline::DB_VERSION );
	}

	public static function installVisitorsOnlineTable()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'page_visitors_online';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			visit_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			page_id tinytext NOT NULL,
			user_hash text NOT NULL,
			PRIMARY KEY id (id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	public static  function installVisitorsDailyTable()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'page_visitors_daily';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			visit_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			page_id tinytext NOT NULL,
			user_hash text NOT NULL,
			PRIMARY KEY id (id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	public function pageVisitorsOnlineShortcode()
	{
		global $wpdb;

//		$total_stats = $wpdb->prefix . 'kento_pvc';
//		$total = $wpdb->get_var( "SELECT count FROM $total_stats WHERE page_id = $post->ID LIMIT 1" );

		$onlineVisitorsTable = $wpdb->prefix . self::VISITORS_ONLINE_DB;
		$dailyVisitorsTable = $wpdb->prefix . self::VISITORS_DAILY_DB;

		global $post;

		$page = $_SERVER['REQUEST_URI'];
		$total = null;
		$daily = $wpdb->get_var( "SELECT COUNT(id) FROM {$dailyVisitorsTable} WHERE page_id = $post->ID LIMIT 1" )?: 0;

		$onlineVisitors = $wpdb->get_var( "SELECT COUNT(id) FROM $onlineVisitorsTable WHERE page_id = '{$page}'" );
		return 'Просмотров: <b>за все время: </b>' . $total .'<b>, за сегодня: </b>' . $daily . '.<b> Читают сейчас: </b>' . $onlineVisitors;
	}
}

$pageVisitorsOnline = new PageVisitorsOnline();