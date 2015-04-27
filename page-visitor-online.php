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

	public $table_name;

	public function __construct()
	{
		add_action( 'init', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array($this, 'install') );
	}

	public function init()
	{
		global $wpdb;

		$user = isset($_COOKIE['pvo_hash']) ? $_COOKIE['pvo_hash'] : null;

		if (!is_admin() && !$user) {
			$user = uniqid() . uniqid();

			setcookie('pvo_hash', $user, time()+3600*24*100);
		}

		global $wpdb;
		global $post;

		$table_name = $wpdb->prefix . 'page_visitors_online';
		$postID = $post->ID;

		$wpdb->query(
			$wpdb->prepare(
				"
                DELETE FROM $wpdb->postmeta
				 WHERE user_hash = %s
				",
				$user
			)
		);
		
		$wpdb->insert($table_name , array(
				'visit_time' => date('Y-m-d H:i:s'),
				'page_id' => $postID,
				'user_hash' => $user
			)
		);
	}

	public function install()
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

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'pvo_db_version', PageVisitorsOnline::DB_VERSION );
	}
}

$pageVisitorsOnline = new PageVisitorsOnline();