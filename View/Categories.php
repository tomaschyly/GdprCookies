<?php
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

namespace ChylyGDPRCookiesView;

use ChylyGDPRCookiesModel\Template;
use WP_Query;

class Categories {

	const PAGE_SLUG = "chyly-gdpr-cookies-categories";
	const PAGE_SLUG_SUBMIT = "?action=chyly_gdpr_cookies_categories_submit";
	const PAGE_AJAX_SUBMIT = "chyly_gdpr_cookies_categories_submit";
	const COOKIE_MESSAGES = "chyly_gdpr_cookies_categories_messages";

	public static $Instance = null;
	public static $template = [
		'Page/Categories.php' => 'Privacy Policy Categories page'
	];

	private $renderTitle = true;

	/**
	 * Categories initialization.
	 */
	public function __construct ($renderTitle = true) {
		if (self::$Instance != null) {
			return;
		}

		self::$Instance = $this;

		$this->renderTitle = $renderTitle;

		$this->InitContent ();
	}

	/**
	 * Categories WP plugin activation.
	 */
	public static function WpActivate () {
		$the_query = new WP_Query ([
			'post_type' => 'page',
			'posts_per_page' => -1,
			'orderby' => 'title',
			'order' => 'ASC',
			'post_status' => 'trash'
		]);

		$exists = false;
		while ($the_query->have_posts ()) {
			$the_query->the_post ();

			if (strpos (get_post_field ('post_name'), self::PAGE_SLUG) !== false) {
				wp_untrash_post (get_the_ID ());

				wp_update_post ([
					'ID' => get_the_ID (),
					'post_content' => '[' . str_replace ('-', '_', self::PAGE_SLUG) . '_categories]'
				]);

				$exists = true;
			}
		}

		wp_reset_postdata ();

		if (!$exists) {
			foreach (self::$template as $i => $v) {
				$title = __ ($v, \ChylyGDPRCookies::ID);
				if (function_exists ('pll__')) {
					$title = pll__ ($v);
				}

				$pageID = wp_insert_post ([
					'post_type' => 'page',
					'post_title' => $title,
					'post_name' => self::PAGE_SLUG,
					'post_content' => '[' . str_replace ('-', '_', self::PAGE_SLUG) . '_categories]',
					'post_status' => 'publish',
					'post_author' => 1
				]);

				/*update_post_meta ($pageID, '_wp_page_template', $i);*/
			}
		}
	}

	/**
	 * Categories WP plugin deactivation.
	 */
	public static function WpDeactivate () {
		$the_query = new WP_Query ([
			'post_type' => 'page',
			'posts_per_page' => 1,
			'orderby' => 'title',
			'order' => 'ASC',
			'pagename' => self::PAGE_SLUG
		]);

		if ($the_query->have_posts ()) {
			$the_query->the_post ();

			wp_trash_post (get_the_ID ());
		}

		wp_reset_postdata ();
	}

	/**
	 * Categories WP initialization.
	 */
	public static function WPInit () {
		add_filter ('theme_page_templates', ['ChylyGDPRCookiesView\Categories', 'AddTemplate']);
		add_filter ('wp_insert_post_data', ['ChylyGDPRCookiesView\Categories', 'RegisterTemplate']);
		add_filter ('template_include', ['ChylyGDPRCookiesView\Categories', 'ViewTemplate']);

		add_shortcode (str_replace ('-', '_', self::PAGE_SLUG) . '_categories', ['ChylyGDPRCookiesView\Categories', 'PageContent']);

		add_action ('wp_ajax_' . self::PAGE_AJAX_SUBMIT, ['ChylyGDPRCookiesView\Categories', 'FormSubmit']);
		add_action ('wp_ajax_nopriv_' . self::PAGE_AJAX_SUBMIT, ['ChylyGDPRCookiesView\Categories', 'FormSubmit']);
	}

	/**
	 * Add Categories page template.
	 */
	public static function AddTemplate ($templates) {
		$templates = array_merge ($templates, self::$template);

		return $templates;
	}

	/**
	 * Insert Categories page template into page cache.
	 */
	public static function RegisterTemplate ($attributes) {
		$cacheKey = 'page_templates-' . md5 (get_theme_root () . '/' . get_stylesheet ());

		$templates = wp_get_theme ()->get_page_templates ();
		if (!is_array ($templates) || count ($templates) <= 0) {
			$templates = [];
		}

		wp_cache_delete ($cacheKey, 'themes');

		$templates = array_merge ($templates, self::$template);

		wp_cache_add ($cacheKey, $templates, 'themes', 1800);

		return $attributes;
	}

	/**
	 * Return Categories page template path if current page uses it.
	 */
	public static function ViewTemplate ($template) {
		global $post;

		if (!$post) {
			return $template;
		}

		if (!isset (self::$template [get_post_meta ($post->ID, '_wp_page_template', true)])) {
			return $template;
		}

		$filePath = \ChylyGDPRCookies::GetPluginDirPath () . get_post_meta ($post->ID, '_wp_page_template', true);

		if (file_exists ($filePath)) {
			return $filePath;
		}

		return $template;
	}

	/**
	 * Render Categories page content.
	 */
	public static function PageContent ($args) {
		ob_start ();
		new Categories (isset ($args ['title']) && $args ['title'] == 'false' ? false : true);
		$content = ob_get_contents ();
		ob_end_clean ();

		return $content;
	}

	/**
	 * Check if consent given for category.
	 */
	public static function ConsentGiven ($for) {
		return isset ($_COOKIE [$for]) && $_COOKIE [$for] == 1;
	}

	/**
	 * On form submit update settings.
	 */
	public static function FormSubmit () {
		$data = $_POST;

		if (isset ($data ['username']) && $data ['username'] != '') {
			wp_redirect ($_SERVER ['HTTP_REFERER'], 302);
			exit;
		}

		$introCookieSeenLifespan = Admin::GetOption (Admin::$SettingsOptionsGroups ['intro'], 'cookie_seen_lifespan');

		setcookie (Intro::COOKIE_INTRO_SEEN, "1", time () + $introCookieSeenLifespan, "/");

		if (isset ($data ['analytics_category'])) {
			setcookie (Intro::COOKIE_ANALYTICS, "1", time () + $introCookieSeenLifespan, "/");
		} else {
			setcookie (Intro::COOKIE_ANALYTICS, "0", time () - 30, "/");
		}

		if (isset ($data ['marketing_category'])) {
			setcookie (Intro::COOKIE_MARKETING, "1", time () + $introCookieSeenLifespan, "/");
		} else {
			setcookie (Intro::COOKIE_MARKETING, "0", time () - 30, "/");
		}

		$message = function_exists ('pll__') ? pll__ ('Settings updated') : __ ('Settings updated', \ChylyGDPRCookies::ID);
		setcookie (self::COOKIE_MESSAGES, base64_encode (serialize ([$message])), time () + $introCookieSeenLifespan, "/");

		wp_redirect ($_SERVER ['HTTP_REFERER'], 302);
		exit;
	}

	/**
	 * Initialize content with template and data.
	 */
	private function InitContent () {
		$template = new Template ('Categories');

		$template->Insert ('styleCss', \ChylyGDPRCookies::GetPluginUrl ('css/style.css'));
		$template->Insert ('settingsJs', Admin::ToJson ());
		$template->Insert ('functionsJs', \ChylyGDPRCookies::GetPluginUrl ('js/functions.js'));

		$messages = '';
		if (isset ($_COOKIE [self::COOKIE_MESSAGES])) {
			$_messages = unserialize (base64_decode ($_COOKIE [self::COOKIE_MESSAGES]));

			if (is_array ($_messages) && count ($_messages) > 0) {
				foreach ($_messages as $i => $v) {
					$messages .= '<p class="message">' . $v . '</p>';
				}
			}

			setcookie (self::COOKIE_MESSAGES, serialize ([]), time () - 30, "/");
		}
		$template->Insert ('messages', $messages);

		if ($this->renderTitle) {
			$titleContainer = '<div class="col-xs-12 col-12">
	<h1>{$title}</h1>
</div>';
			$template->Insert ('titleContainer', $titleContainer);
		} else {
			$template->Insert ('titleContainer', '');
		}

		$afterTitle = apply_filters (str_replace ('-', '_', \ChylyGDPRCookies::ID) . '_categories_afterTitle', '');
		$template->Insert ('afterTitle', $afterTitle);

		$cookiesData = $this->PageData (Admin::GetOption (Admin::$SettingsOptionsGroups ['categories'], 'cookies_content'));
		$template->Insert ('cookiesContent', $cookiesData ['content']);

		$necessaryData = $this->PageData (Admin::GetOption (Admin::$SettingsOptionsGroups ['categories'], 'necessary_content'));
		$template->Insert ('necessaryTitle', $necessaryData ['title']);
		$template->Insert ('necessaryContent', $necessaryData ['content']);

		$template->Insert ('submitUrl', admin_url ('admin-ajax.php') . '/' . self::PAGE_SLUG_SUBMIT);

		$analyticsData = $this->PageData (Admin::GetOption (Admin::$SettingsOptionsGroups ['categories'], 'analytics_content'));
		$template->Insert ('analyticsTitle', $analyticsData ['title']);
		$template->Insert ('analyticsContent', $analyticsData ['content']);
		$analyticsChecked = '';
		if (self::ConsentGiven (Intro::COOKIE_ANALYTICS)) {
			$analyticsChecked = 'checked="checked"';
		}
		$template->Insert ('analyticsChecked', $analyticsChecked);

		$marketingData = $this->PageData (Admin::GetOption (Admin::$SettingsOptionsGroups ['categories'], 'marketing_content'));
		$template->Insert ('marketingTitle', $marketingData ['title']);
		$template->Insert ('marketingContent', $marketingData ['content']);
		$marketingChecked = '';
		if (self::ConsentGiven (Intro::COOKIE_MARKETING)) {
			$marketingChecked = 'checked="checked"';
		}
		$template->Insert ('marketingChecked', $marketingChecked);

		if (function_exists ('pll__')) {
			$template->Insert ('title', pll__ ('Privacy Policy Categories'));
			$template->Insert ('analyticsEnabled', pll__ ('Analytics Enabled'));
			$template->Insert ('marketingEnabled', pll__ ('Marketing Enabled'));
			$template->Insert ('agreeAll', pll__ ('Agree to All'));
			$template->Insert ('save', pll__ ('Save'));
		} else {
			$template->Insert ('title', __ ('Privacy Policy Categories', \ChylyGDPRCookies::ID));
			$template->Insert ('analyticsEnabled', __ ('Analytics Enabled', \ChylyGDPRCookies::ID));
			$template->Insert ('marketingEnabled', __ ('Marketing Enabled', \ChylyGDPRCookies::ID));
			$template->Insert ('agreeAll', __ ('Agree to All', \ChylyGDPRCookies::ID));
			$template->Insert ('save', __ ('Save', \ChylyGDPRCookies::ID));
		}

		$template->Render ();
	}

	/**
	 * Get Page title and content by ID.
	 */
	private function PageData ($pageID) {
		$return = [
			'title' => '',
			'content' => ''
		];

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

				$return ['title'] = get_the_title ();
				$return ['content'] = apply_filters ('the_content', get_the_content ());
			}

			wp_reset_postdata ();
		}

		return $return;
	}

}
