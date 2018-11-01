<?php
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

namespace ChylyGDPRCookiesView;

class Scripts {

	const GLOBALS_SCRIPTS = "chyly_gdpr_cookies_scripts";

	public static $Instance = null;

	/**
	 * Scripts initialization.
	 */
	public function __construct () {
		if (self::$Instance != null) {
			return;
		}

		self::$Instance = $this;

		$this->InitContent ();
	}

	/**
	 * Scripts WP Plugins Loaded initialization.
	 */
	public static function WPPluginsLoadedInit () {
		self::RemoveGAGoogleAnalytics ();
	}

	/**
	 * Scripts WP Print Scripts initialization.
	 */
	public static function WPPrintScriptsInit () {
		self::RemoveScripts ();
	}

	/**
	 * Scripts WP Render initialization.
	 */
	public static function WPRenderInit () {
		new Scripts ();
	}

	/**
	 * Remove GA Google Analytics plugin scripts.
	 */
	public static function RemoveGAGoogleAnalytics () {
		if (Admin::GetOption (Admin::$SettingsOptionsGroups ['general'], 'filter_ga_google_analytics') == "1") {
			$analyticsEnabled = Categories::ConsentGiven (Intro::COOKIE_ANALYTICS);

			if (Admin::GetOption (Admin::$SettingsOptionsGroups ['general'], 'type') == "1") {
				$analyticsEnabled = false;
			}

			if (!$analyticsEnabled) {
				remove_action ('wp_head', 'ga_google_analytics_tracking_code');
				remove_action ('wp_footer', 'ga_google_analytics_tracking_code');

				if (function_exists ('ga_google_analytics_tracking_code')) {
					ob_start ();
					ga_google_analytics_tracking_code ();
					$gaScript = ob_get_contents ();
					ob_end_clean ();

					if (strlen (trim ($gaScript)) > 0) {
						$scriptsHtml = '<script class="' . \ChylyGDPRCookies::ID . '-script">if (typeof (TCH) !== "undefined" && typeof (TCH.GDPRCookies) !== "undefined" && typeof (TCH.GDPRCookies.analyticsScripts) !== "undefined") { TCH.GDPRCookies.analyticsScripts.push ("' . base64_encode ($gaScript) . '"); } else { if (typeof (TCH_GDPRCookies_analyticsScripts) === "undefined") { var TCH_GDPRCookies_analyticsScripts = []; } TCH_GDPRCookies_analyticsScripts.push ("' . base64_encode ($gaScript) . '"); }</script>';

						if (is_string ($GLOBALS [self::GLOBALS_SCRIPTS])) {
							$GLOBALS [self::GLOBALS_SCRIPTS] = base64_encode (base64_decode ($GLOBALS [self::GLOBALS_SCRIPTS]) . $scriptsHtml);
						} else {
							$GLOBALS [self::GLOBALS_SCRIPTS] = base64_encode ($scriptsHtml);
						}
					}
				}
			}
		}
	}

	/**
	 * Remove all js files that do not have consent to be used.
	 */
	private static function RemoveScripts () {
		$analyticsEnabled = Categories::ConsentGiven (Intro::COOKIE_ANALYTICS);
		$marketingEnabled = Categories::ConsentGiven (Intro::COOKIE_MARKETING);

		if (Admin::GetOption (Admin::$SettingsOptionsGroups ['general'], 'type') == "1") {
			$analyticsEnabled = false;
			$marketingEnabled = false;
		}

		$scriptsHtml = '';

		if ((!$analyticsEnabled || !$marketingEnabled) && Admin::GetOption (Admin::$SettingsOptionsGroups ['general'], 'js_filter_enabled') == 1) {
			global $wp_scripts;

			if (isset ($wp_scripts->queue) && is_array ($wp_scripts->queue) && count ($wp_scripts->queue) > 0) {
				$analyticsScripts = self::Scripts ('analytics_scripts');
				$marketingScripts = self::Scripts ('marketing_scripts');

				foreach ($wp_scripts->queue as $i => $v) {
					$removed = false;

					if (!$analyticsEnabled) {
						foreach ($analyticsScripts as $av) {
							if (strpos ($v, $av) !== false) {
								ob_start ();
								$wp_scripts->do_item ($v);
								$scriptHtml = ob_get_contents ();
								ob_end_clean ();

								$scriptsHtml .= '<script class="' . \ChylyGDPRCookies::ID . '-script">if (typeof (TCH) !== "undefined" && typeof (TCH.GDPRCookies) !== "undefined" && typeof (TCH.GDPRCookies.analyticsScripts) !== "undefined") { TCH.GDPRCookies.analyticsScripts.push ("' . base64_encode ($scriptHtml) . '"); } else { if (typeof (TCH_GDPRCookies_analyticsScripts) === "undefined") { var TCH_GDPRCookies_analyticsScripts = []; } TCH_GDPRCookies_analyticsScripts.push ("' . base64_encode ($scriptHtml) . '"); }</script>';

								wp_deregister_script ($v);

								$removed = true;
								break;
							}
						}
					}

					if (!$removed && !$marketingEnabled) {
						foreach ($marketingScripts as $av) {
							if (strpos ($v, $av) !== false) {
								ob_start ();
								$wp_scripts->do_item ($v);
								$scriptHtml = ob_get_contents ();
								ob_end_clean ();

								$scriptsHtml .= '<script class="' . \ChylyGDPRCookies::ID . '-script">if (typeof (TCH) !== "undefined" && typeof (TCH.GDPRCookies) !== "undefined" && typeof (TCH.GDPRCookies.marketingScripts) !== "undefined") { TCH.GDPRCookies.marketingScripts.push ("' . base64_encode ($scriptHtml) . '"); } else { if (typeof (TCH_GDPRCookies_marketingScripts) === "undefined") { var TCH_GDPRCookies_marketingScripts = []; } TCH_GDPRCookies_marketingScripts.push ("' . base64_encode ($scriptHtml) . '"); }</script>';

								wp_deregister_script ($v);
								break;
							}
						}
					}
				}
			}
		}

		if (is_string ($GLOBALS [self::GLOBALS_SCRIPTS])) {
			$GLOBALS [self::GLOBALS_SCRIPTS] = base64_encode (base64_decode ($GLOBALS [self::GLOBALS_SCRIPTS]) . $scriptsHtml);
		} else {
			$GLOBALS [self::GLOBALS_SCRIPTS] = base64_encode ($scriptsHtml);
		}
	}

	/**
	 * Get list of scripts for category.
	 */
	private static function Scripts ($for) {
		$scripts = [];
		$scriptsAsText = Admin::GetOption (Admin::$SettingsOptionsGroups ['scripts'], $for);

		if (is_string ($scriptsAsText) && strlen (trim ($scriptsAsText)) > 0) {
			$scripts = explode ("\n", $scriptsAsText);

			foreach ($scripts as & $v) {
				$v = trim ($v);
			}
		}

		return $scripts;
	}

	/**
	 * Get custom scripts which have consent.
	 */
	public static function CustomScripts () {
		$analyticsEnabled = Categories::ConsentGiven (Intro::COOKIE_ANALYTICS);
		$marketingEnabled = Categories::ConsentGiven (Intro::COOKIE_MARKETING);

		$html = '';

		if ($analyticsEnabled) {
			$html .= Admin::GetOption (Admin::$SettingsOptionsGroups ['scripts'], 'custom_analytics_scripts');
		}

		if ($marketingEnabled) {
			$html .= Admin::GetOption (Admin::$SettingsOptionsGroups ['scripts'], 'custom_marketing_scripts');
		}

		$result = [
			'scriptsHTML' => base64_encode ($html)
		];

		echo json_encode ($result);
		exit;
	}

	/**
	 * Initialize content with scripts.
	 */
	private function InitContent () {
		if (isset ($GLOBALS [self::GLOBALS_SCRIPTS]) && $GLOBALS [self::GLOBALS_SCRIPTS] != '') {
			echo base64_decode ($GLOBALS [self::GLOBALS_SCRIPTS]);

			$GLOBALS [self::GLOBALS_SCRIPTS] = '';
		}

		$analyticsEnabled = Categories::ConsentGiven (Intro::COOKIE_ANALYTICS);
		$marketingEnabled = Categories::ConsentGiven (Intro::COOKIE_MARKETING);

		if (Admin::GetOption (Admin::$SettingsOptionsGroups ['general'], 'type') == "1") {
			$analyticsEnabled = false;
			$marketingEnabled = false;
		}

		if ($analyticsEnabled) {
			echo Admin::GetOption (Admin::$SettingsOptionsGroups ['scripts'], 'custom_analytics_scripts');
		}

		if ($marketingEnabled) {
			echo Admin::GetOption (Admin::$SettingsOptionsGroups ['scripts'], 'custom_marketing_scripts');
		}
	}

}
