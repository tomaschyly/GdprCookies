<?php
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

namespace ChylyGDPRCookiesView;

use ChylyGDPRCookiesModel\Template;
use WP_Query;

class Admin {

	const PAGE_SLUG = "chyly-gdpr-cookies";

	public static $Instance = null;
	public static $SettingsOptionsGroups = [
		'general' => 'chyly_gdpr_cookies_general',
		'intro' => 'chyly_gdpr_cookies_intro',
		'scripts' => 'chyly_gdpr_cookies_scripts',
		'categories' => 'chyly_gdpr_cookies_categories',
		'geoip' => 'chyly_gdpr_cookies_geoip'
	];
	public static $FrontendTypes = [
		0 => 'Php',
		1 => 'Js'
	];

	private static $Options = [
		'general' => [
			'js_filter_enabled' => [
				'JS Filter Enabled',
				'OptionsTrueFalseFieldContent'
			],
			'type' => [
				'Frontend Type',
				'OptionsTypeSelectFieldContent'
			],
			'filter_ga_google_analytics' => [
				'JS Filter <em>GA Google Analytics</em> Plugin',
				'OptionsTrueFalseFieldContent'
			]
		],
		'intro' => [
			'cookie_seen_lifespan' => [
				'Cookie Intro Seen Lifespan',
				'OptionsFieldContent'
			],
			'content' => [
				'Content Page',
				'OptionsPageSelectFieldContent'
			]
		],
		'scripts' => [
			'analytics_scripts' => [
				'Analytics Scripts',
				'OptionsTextAreaContent'
			],
			'custom_analytics_scripts' => [
				'Custom Analytics Scripts',
				'OptionsTextAreaContent'
			],
			'marketing_scripts' => [
				'Marketing Scripts',
				'OptionsTextAreaContent'
			],
			'custom_marketing_scripts' => [
				'Custom Marketing Scripts',
				'OptionsTextAreaContent'
			]
		],
		'categories' => [
			'cookies_content' => [
				'Cookies Content',
				'OptionsPageSelectFieldContent'
			],
			'necessary_content' => [
				'Necessary Content',
				'OptionsPageSelectFieldContent'
			],
			'analytics_content' => [
				'Analytics Content',
				'OptionsPageSelectFieldContent'
			],
			'marketing_content' => [
				'Marketing Content',
				'OptionsPageSelectFieldContent'
			]
		],
		'geoip' => [
			'geoip_enabled' => [
				'GeoIp Enabled',
				'OptionsTrueFalseFieldContent'
			],
			'last_updated' => [
				'Last Database Update',
				'OptionsFieldDisabledContent'
			]
		]
	];
	private static $pagesAsOptions = null;

	/**
	 * Admin initialization.
	 */
	public function __construct () {
		if (self::$Instance != null) {
			return;
		}

		self::$Instance = $this;

		$this->InitOptions ();
	}

	/**
	 * Admin WP initialization.
	 */
	public static function WPInit () {
		new Admin ();
	}

	/**
	 * Admin WP menu initialization.
	 */
	public static function WPMenuInit () {
		add_options_page (__ ('GDPR Cookies', \ChylyGDPRCookies::ID), __ ('GDPR Cookies', \ChylyGDPRCookies::ID), 'manage_options', self::PAGE_SLUG, ['ChylyGDPRCookiesView\Admin', 'PageContent']);
	}

	/**
	 * Get option from settings.
	 */
	public static function GetOption ($group, $key) {
		return get_option ($group . "_" . $key);
	}

	/**
	 * Initialize options settings.
	 */
	private function InitOptions () {
		foreach (self::$SettingsOptionsGroups as $i => $v) {
			foreach (self::$Options [$i] as $oi => $ov) {
				register_setting ($v, $v . '_' . $oi);
			}

			add_settings_section ($v, __ (ucfirst ($i), \ChylyGDPRCookies::ID), ['ChylyGDPRCookiesView\Admin', 'OptionsSectionContent'], $v);

			foreach (self::$Options [$i] as $oi => $ov) {
				add_settings_field ($v . '_' . $oi, __ ($ov [0], \ChylyGDPRCookies::ID), ['ChylyGDPRCookiesView\Admin', $ov [1]], $v, $v, [
					'id' => $v . '_' . $oi,
					'label_for' => $v . '_' . $oi
				]);
			}
		}

		$jsFilterEnabled = self::GetOption (self::$SettingsOptionsGroups ['general'], 'js_filter_enabled');
		if ($jsFilterEnabled === false) {
			update_option (self::$SettingsOptionsGroups ['general'] . '_js_filter_enabled', "1");
		}
		$frontendType = self::GetOption (self::$SettingsOptionsGroups ['general'], 'type');
		if ($frontendType === false) {
			update_option (self::$SettingsOptionsGroups ['general'] . '_type', "0");
		}
		$introCookieSeenLifespan = self::GetOption (self::$SettingsOptionsGroups ['intro'], 'cookie_seen_lifespan');
		if ($introCookieSeenLifespan === false) {
			update_option (self::$SettingsOptionsGroups ['intro'] . '_cookie_seen_lifespan', (60 * 60 * 24 * 30));
		}
	}

	/**
	 * Options section html content.
	 */
	public static function OptionsSectionContent ($params) {
		//do nothing
	}

	/**
	 * Options field html content.
	 */
	public static function OptionsFieldContent ($params) {
		$value = get_option ($params ['id']);

		$template = new Template ('Field');

		$template->Insert ('id', $params ['id']);

		$template->Insert ('value', $value);

		$template->Render ();
	}

	/**
	 * Options field html content.
	 */
	public static function OptionsFieldDisabledContent ($params) {
		$value = get_option ($params ['id']);

		$template = new Template ('FieldDisabled');

		$template->Insert ('id', $params ['id']);

		$template->Insert ('value', $value);

		$template->Render ();
	}

	/**
	 * Options field html content.
	 */
	public static function OptionsTextAreaContent ($params) {
		$value = get_option ($params ['id']);

		$template = new Template ('TextArea');

		$template->Insert ('id', $params ['id']);

		$template->Insert ('value', $value);

		$template->Render ();
	}

	/**
	 * Options field html content.
	 */
	public static function OptionsTrueFalseFieldContent ($params) {
		$value = get_option ($params ['id']);

		$template = new Template ('TrueFalse');

		$template->Insert ('id', $params ['id']);

		$template->Insert ('No', __ ('No', \ChylyGDPRCookies::ID));
		$template->Insert ('Yes', __ ('Yes', \ChylyGDPRCookies::ID));

		$template->Insert ('Selected', ($value == 1 ? 'selected="selected"' : ''));

		$template->Render ();
	}

	/**
	 * Options field html content.
	 */
	public static function OptionsTypeSelectFieldContent ($params) {
		$value = get_option ($params ['id']);

		$template = new Template ('Select');

		$template->Insert ('id', $params ['id']);

		$template->InsertOptions ('options', self::$FrontendTypes, $value);

		$template->Render ();
	}

	/**
	 * Options field html content.
	 */
	public static function OptionsPageSelectFieldContent ($params) {
		$value = get_option ($params ['id']);

		$template = new Template ('Select');

		$template->Insert ('id', $params ['id']);

		$template->InsertOptions ('options', self::GetPages (), $value);

		$template->Render ();
	}

	/**
	 * Admin page html content.
	 */
	public static function PageContent () {
		$template = new Template ('Admin');

		$template->Insert ('title', __ ('GDPR Cookies', \ChylyGDPRCookies::ID));

		foreach (self::$SettingsOptionsGroups as $i => $v) {
			ob_start ();
			settings_fields ($v);
			do_settings_sections ($v);
			submit_button ();
			$formFields = ob_get_contents ();
			ob_end_clean ();
			$template->Insert ($i . 'FormFields', $formFields);
		}

		$template->Render ();
	}

	/**
	 * Get Pages for select.
	 */
	private static function GetPages () {
		if (self::$pagesAsOptions != null) {
			return self::$pagesAsOptions;
		}

		$pages = [
			0 => __ ('— Select Page — ', \ChylyGDPRCookies::ID)
		];
		$the_query = new WP_Query ([
			'post_type' => 'page',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC'
		]);
		if ($the_query->have_posts ()) {
			while ($the_query->have_posts ()) {
				$the_query->the_post ();

				$pages [get_the_ID ()] = get_the_title () . ' (' . get_post_field ('post_name', get_the_ID ()) . ')';
			}
		}

		self::$pagesAsOptions = $pages;
		return self::$pagesAsOptions;
	}

	/**
	 * Get settings needed for JS in JSON.
	 */
	public static function ToJson () {
		$json = [
			'general' => [
				'js_filter_enabled' => self::GetOption (self::$SettingsOptionsGroups ['general'], 'js_filter_enabled'),
				'type' => self::GetOption (self::$SettingsOptionsGroups ['general'], 'type')
			],
			'intro' => [
				'cookie_seen_lifespan' => self::GetOption (self::$SettingsOptionsGroups ['intro'], 'cookie_seen_lifespan'),
				'cookie_intro_seen' => Intro::COOKIE_INTRO_SEEN,
				'cookie_intro_analytics' => Intro::COOKIE_ANALYTICS,
				'cookie_intro_marketing' => Intro::COOKIE_MARKETING
			],
			'scripts' => [
				'request_url' => '/wp-admin/admin-ajax.php?action=' . str_replace ('-', '_', \ChylyGDPRCookies::ID) . '_scripts'
			],
			'geoip' => [
				'geoip_enabled' => self::GetOption (self::$SettingsOptionsGroups ['geoip'], 'geoip_enabled'),
				'request_url' => '/wp-admin/admin-ajax.php?action=' . str_replace ('-', '_', \ChylyGDPRCookies::ID) . '_isineu'
			]
		];

		return json_encode ($json);
	}
	
}
