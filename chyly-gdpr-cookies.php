<?php
/*
Plugin Name: Chyly GDPR Cookies
Version: 1.4
Description: Ask users for consent before non-necessary cookies from scripts are used. There is also GDPR Privacy Policy explanation shown to users on first visit.
Author: Tomas Chyly
Author URI: https://tomas-chyly.com/en/
Plugin URI: https://tomas-chyly.com/en/
Text Domain: chyly-gdpr-cookies
Domain Path: /languages
License: GPL v3

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

if (!defined ('ABSPATH')) {
	exit;
}

require_once dirname (__FILE__) . '/Loader.php';

class ChylyGDPRCookies {

	const ID = "chyly-gdpr-cookies";

	public static $Instance = null;

	/**
	 * Chyly GDPR Cookies initialization.
	 */
	public function __construct () {
		if (self::$Instance != null) {
			return;
		}

		self::$Instance = $this;

		if (is_admin ()) {
			add_action ('admin_init', ['ChylyGDPRCookiesView\Admin', 'WPInit']);
			add_action ('admin_menu', ['ChylyGDPRCookiesView\Admin', 'WPMenuInit']);
		} else {
			\ChylyGDPRCookiesView\Intro::WPInit ();
			add_action ('wp_footer', ['ChylyGDPRCookiesView\Intro', 'WPFooterInit'], 99);
			add_action ('plugins_loaded', ['ChylyGDPRCookiesView\Scripts', 'WPPluginsLoadedInit'], 99);
			add_action ('wp_print_scripts', ['ChylyGDPRCookiesView\Scripts', 'WPPrintScriptsInit'], 99);
			add_action ('wp_footer', ['ChylyGDPRCookiesView\Scripts', 'WPRenderInit'], 99);
		}

		add_action ('plugins_loaded', [$this, 'RegisterTextDomain']);
	}

	/**
	 * Chyly GDPR Cookies WP initialization.
	 */
	public static function WPInit () {
		register_activation_hook (__FILE__, ["ChylyGDPRCookies", "WpActivate"]);
		register_deactivation_hook (__FILE__, ["ChylyGDPRCookies", "WpDeactivate"]);
		register_uninstall_hook (__FILE__, ["ChylyGDPRCookies", "WpUninstall"]);

		add_filter ("plugin_action_links_" . self::GetPluginBasename (), ["ChylyGDPRCookies", "PluginActionLinks"]);

		add_action ('wp_ajax_' . str_replace ('-', '_', self::ID) . '_isineu', ['ChylyGDPRCookiesView\Intro', 'IsInEuAction']);
		add_action ('wp_ajax_nopriv_' . str_replace ('-', '_', self::ID) . '_isineu', ['ChylyGDPRCookiesView\Intro', 'IsInEuAction']);
		add_action ('wp_ajax_' . str_replace ('-', '_', self::ID) . '_agree', ['ChylyGDPRCookiesView\Intro', 'AgreeAction']);
		add_action ('wp_ajax_nopriv_' . str_replace ('-', '_', self::ID) . '_agree', ['ChylyGDPRCookiesView\Intro', 'AgreeAction']);
		add_action ('wp_ajax_' . str_replace ('-', '_', self::ID) . '_purposes', ['ChylyGDPRCookiesView\Intro', 'PurposesAction']);
		add_action ('wp_ajax_nopriv_' . str_replace ('-', '_', self::ID) . '_purposes', ['ChylyGDPRCookiesView\Intro', 'PurposesAction']);
		add_action ('wp_ajax_' . str_replace ('-', '_', self::ID) . '_scripts', ['ChylyGDPRCookiesView\Scripts', 'CustomScripts']);
		add_action ('wp_ajax_nopriv_' . str_replace ('-', '_', self::ID) . '_scripts', ['ChylyGDPRCookiesView\Scripts', 'CustomScripts']);

		\ChylyGDPRCookiesView\Categories::WPInit ();

		new ChylyGDPRCookies ();
	}

	/**
	 * Plugin is activated.
	 */
	public static function WpActivate () {
		\ChylyGDPRCookiesView\Categories::WpActivate ();
		\ChylyGDPRCookiesModel\GeoIp::WpActivate ();
	}

	/**
	 * Plugin has been deactivated.
	 */
	public static function WpDeactivate () {
		\ChylyGDPRCookiesView\Categories::WpDeactivate ();
	}

	/**
	 * Plugin is uninstalled from Wordpress.
	 */
	public static function WpUninstall () {
		//uninstall.php
	}

	/**
	 * Adds "Settings" link to module in administrator module list.
	 */
	public static function PluginActionLinks ($links) {
		/** @noinspection HtmlUnknownTarget */
		$links [] = '<a href="options-general.php?page=' . \ChylyGDPRCookiesView\Admin::PAGE_SLUG . '">' . __ ('Settings', self::ID) . '</a>';

		return $links;
	}

	/**
	 * Gets plugin basename.
	 */
	public static function GetPluginBasename () {
		return plugin_basename (__FILE__);
	}

	/**
	 * Gets plugin directory path.
	 */
	public static function GetPluginDirPath () {
		return plugin_dir_path (__FILE__);
	}

	/**
	 * Gets plugin url path.
	 */
	public static function GetPluginUrl ($path = '') {
		return plugins_url (self::ID . '/' . $path, dirname (__FILE__));
	}

	/**
	 * Register plugin text domain.
	 */
	public function RegisterTextDomain () {
		load_plugin_textdomain (self::ID, false, dirname (plugin_basename (__FILE__)) . '/languages');

		if (function_exists ('pll_register_string')) {
			require_once dirname (__FILE__) . '/polylang.php';
		}
	}

}

ChylyGDPRCookies::WPInit ();
