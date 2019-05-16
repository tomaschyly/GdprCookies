<?php
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

namespace ChylyGDPRCookiesView;

use ChylyGDPRCookiesModel\GeoIp;
use ChylyGDPRCookiesModel\Template;
use WP_Query;

class Intro {

	const COOKIE_INTRO_SEEN = 'chyly_gdpr_cookies_intro_seen';
	const COOKIE_ANALYTICS = 'chyly_gdpr_cookies_analytics';
	const COOKIE_MARKETING = 'chyly_gdpr_cookies_marketing';
	const GLOBALS_INTRO = 'chyly_gdpr_cookies_intro';

	public static $Instance = null;

	/**
	 * Intro initialization.
	 */
	public function __construct () {
		if (self::$Instance != null) {
			return;
		}

		self::$Instance = $this;

		$this->InitContent ();
	}

	/**
	 * Intro WP initialization.
	 */
	public static function WPInit () {
		if (Admin::GetOption (Admin::$SettingsOptionsGroups ['general'], 'type') == "0") {
			$introCookieSeenLifespan = Admin::GetOption (Admin::$SettingsOptionsGroups ['intro'], 'cookie_seen_lifespan');

			$geoIp = new GeoIp ();

			if ((!isset ($_COOKIE [self::COOKIE_INTRO_SEEN]) || $_COOKIE [self::COOKIE_INTRO_SEEN] != 1) && $geoIp->IsInEU ()) {
				$GLOBALS [self::GLOBALS_INTRO] = true;
			} else {
				setcookie (self::COOKIE_INTRO_SEEN, "1", time () + $introCookieSeenLifespan, "/");
			}
		} else {
			$GLOBALS [self::GLOBALS_INTRO] = true;
		}
	}

	/**
	 * Intro WP footer initialization.
	 */
	public static function WPFooterInit () {
		if (isset ($GLOBALS [self::GLOBALS_INTRO]) && $GLOBALS [self::GLOBALS_INTRO]) {
			$GLOBALS [self::GLOBALS_INTRO] = false;

			new Intro ();
		}
	}

	/**
	 * Check if is in the EU by IP.
	 */
	public static function IsInEuAction () {
		$geoIp = new GeoIp ();

		echo json_encode ([
			'geoip' => [
				'isInEu' => $geoIp->IsInEU ()
			]
		]);
		exit;
	}

	/**
	 * User agrees, we give consent to all.
	 */
	public static function AgreeAction () {
		$introCookieSeenLifespan = Admin::GetOption (Admin::$SettingsOptionsGroups ['intro'], 'cookie_seen_lifespan');

		setcookie (self::COOKIE_INTRO_SEEN, "1", time () + $introCookieSeenLifespan, "/");
		setcookie (self::COOKIE_ANALYTICS, "1", time () + $introCookieSeenLifespan, "/");
		setcookie (self::COOKIE_MARKETING, "1", time () + $introCookieSeenLifespan, "/");

		$html = Admin::GetOption (Admin::$SettingsOptionsGroups ['scripts'], 'custom_analytics_scripts') . "\n\n" . Admin::GetOption (Admin::$SettingsOptionsGroups ['scripts'], 'custom_marketing_scripts');

		$result = [
			'scriptsHTML' => base64_encode ($html)
		];

		echo json_encode ($result);
		exit;
	}

	/**
	 * User wants to see purposes, we give only basic consent.
	 */
	public static function PurposesAction () {
		$introCookieSeenLifespan = Admin::GetOption (Admin::$SettingsOptionsGroups ['intro'], 'cookie_seen_lifespan');

		setcookie (self::COOKIE_INTRO_SEEN, "1", time () + $introCookieSeenLifespan, "/");

		$the_query = new WP_Query ([
			'post_type' => 'page',
			'posts_per_page' => 1,
			'orderby' => 'title',
			'order' => 'ASC',
			'pagename' => Categories::PAGE_SLUG
		]);

		$pageID = null;
		if ($the_query->have_posts ()) {
			$the_query->the_post ();

			$pageID = get_the_ID ();
		}

		wp_reset_postdata ();

		if ($pageID != null && function_exists ('pll_get_post')) {
			$currentLangID = pll_get_post ($pageID);

			if (intval ($currentLangID) > 0) {
				$pageID = $currentLangID;
			}
		}

		echo json_encode ([
			'id' => $pageID,
			'href' => $pageID != null ? get_permalink ($pageID) : \ChylyGDPRCookies::ID
		]);
		exit;
	}

	/**
	 * Initialize content with template and data.
	 */
	private function InitContent () {
		$template = new Template ('Intro');

		$template->Insert ('styleCss', \ChylyGDPRCookies::GetPluginUrl ('css/style.css'));
		$template->Insert ('settingsJs', Admin::ToJson ());
		$template->Insert ('functionsJs', \ChylyGDPRCookies::GetPluginUrl ('js/functions.js'));

		$pageTitle = '';
		$pageContent = '';
		$pageID = Admin::GetOption (Admin::$SettingsOptionsGroups ['intro'], 'content');
		if (intval ($pageID) > 0) {
			if (function_exists ('pll_get_post')) {
				$currentLangID = pll_get_post ($pageID);

				if (intval ($currentLangID) > 0) {
					$pageID = $currentLangID;
				}
			}

			$the_query = new WP_Query ([
				'post_type' => 'page',
				'posts_per_page' => 1,
				'orderby' => 'title',
				'order' => 'ASC',
				'p' => $pageID
			]);

			if ($the_query->have_posts ()) {
				$the_query->the_post ();

				$pageTitle = get_the_title ();
				$pageContent = apply_filters ('the_content', get_the_content ());
			}

			wp_reset_postdata ();
		}
		$template->Insert ('title', $pageTitle);
		$template->Insert ('content', $pageContent);

		if (function_exists ('pll__')) {
			$template->Insert ('accept', pll__ ('I Accept'));
			$template->Insert ('purposes', pll__ ('Show Purposes'));
		} else {
			$template->Insert ('accept', __ ('I Accept', \ChylyGDPRCookies::ID));
			$template->Insert ('purposes', __ ('Show Purposes', \ChylyGDPRCookies::ID));
		}

		$template->Insert ('agreeUrl', get_site_url () . '/wp-admin/admin-ajax.php?action=' . str_replace ('-', '_', \ChylyGDPRCookies::ID) . '_agree');
		$template->Insert ('purposesUrl', get_site_url () . '/wp-admin/admin-ajax.php?action=' . str_replace ('-', '_', \ChylyGDPRCookies::ID) . '_purposes');

		$template->Render ();
	}

}
