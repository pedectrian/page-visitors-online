<?php
/**
 * Plugin Name: Page visitors online.
 * Version: 0.0.1
 * Author: Alexander Permyakov
 * Author URI: http://ready2dev.ru
 * License: GPL2
 */
namespace Pedectrian;

class PageVisitorsOnline {


	public function __construct()
	{
		add_action('init', array($this, 'init'));
	}

	public function init()
	{
		$user = isset($_COOKIE['pvo_hash']) ? $_COOKIE['pvo_hash'] : null;

		if (!is_admin() && !$user) {
			$user = uniqid() . uniqid();

			setcookie('pvo_hash', $user, time()+3600*24*100);
		}
	}
}