<?php
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

namespace ChylyGDPRCookiesModel;

use ChylyGDPRCookies\Loader;
use ChylyGDPRCookiesView\Admin;
use GeoIp2\Database\Reader;

class GeoIp {

	private $enabled = false;
	/** @var $reader \GeoIp2\Database\Reader */
	private $reader = null;

	/**
	 * GeoIp initialization.
	 */
	public function __construct () {
		$this->enabled = Admin::GetOption (Admin::$SettingsOptionsGroups ['geoip'], 'geoip_enabled') == 1 ? true : false;

		$this->InitReader ();
	}

	/**
	 * GeoIp WP plugin activation.
	 */
	public static function WpActivate () {
		self::DownloadDatabase ();
	}

	/**
	 * Download and extract database from server.
	 */
	public static function DownloadDatabase () {
		ignore_user_abort (true);
		set_time_limit (0);
		ini_set ('default_socket_timeout', 900);
		ini_set ('memory_limit', '3G');

		$databasePath = \ChylyGDPRCookies::GetPluginDirPath () . 'Database/GeoLite2-Country.mmdb';
		$downloadPath = \ChylyGDPRCookies::GetPluginDirPath () . 'Database/GeoLite2-Country.tar.gz';

		$now = date ('mY');

		$tempFile = fopen ($downloadPath, 'w+');
		if (!$tempFile) {
			return null;
		}

		$curl = curl_init ('http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz');
		curl_setopt ($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt ($curl, CURLOPT_FILE, $tempFile);
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_exec ($curl);
		curl_close ($curl);
		fclose ($tempFile);

		if (!file_exists ($downloadPath)) {
			return null;
		}

		$decompress = new \PharData ($downloadPath);
		$decompress->decompress ();
		$decompress = new \PharData (str_replace ('.tar.gz', '.tar', $downloadPath));
		$decompress->extractTo (str_replace ('.tar.gz', '', $downloadPath), null, true);

		$archiveFiles = scandir (str_replace ('.tar.gz', '', $downloadPath));
		foreach ($archiveFiles as $i => $v) {
			if (!in_array ($v, ['.', '..'])) {
				$path = str_replace ('.tar.gz', '', $downloadPath) . '/' . $v;

				$archiveFiles = scandir ($path);
				foreach ($archiveFiles as $i => $v) {
					if (strpos ($v, '.mmdb') !== false) {
						rename ($path . '/' . $v, $databasePath);
						break;
					}
				}

				break;
			}
		}

		Loader::RemoveDirectory (str_replace ('.tar.gz', '', $downloadPath));
		unlink ($downloadPath);
		unlink (str_replace ('.tar.gz', '.tar', $downloadPath));

		update_option (Admin::$SettingsOptionsGroups ['geoip'] . '_last_updated', $now);
	}

	/**
	 * Create new Reader for Database.
	 */
	private function InitReader () {
		if ($this->enabled) {
			$databasePath = $this->DatabasePath ();

			if ($databasePath != null && file_exists ($databasePath)) {
				$this->reader = new Reader ($databasePath);
			}
		}
	}

	/**
	 * Return database filePath and update if needed first.
	 */
	private function DatabasePath () {
		$directory = \ChylyGDPRCookies::GetPluginDirPath () . 'Database';
		if (!file_exists ($directory)) {
			mkdir ($directory, 0777);
		}

		$databasePath = \ChylyGDPRCookies::GetPluginDirPath () . 'Database/GeoLite2-Country.mmdb';
		$downloadPath = \ChylyGDPRCookies::GetPluginDirPath () . 'Database/GeoLite2-Country.tar.gz';
		$lastUpdate = Admin::GetOption (Admin::$SettingsOptionsGroups ['geoip'], 'last_updated');

		$now = date ('mY');

		if ((!file_exists ($databasePath) || $lastUpdate != $now) && !file_exists ($downloadPath)) {
			self::DownloadDatabase ();
		}

		return $databasePath;
	}

	/**
	 * Check if is in EU by IP address.
	 */
	public function IsInEU () {
		if (!$this->enabled || $this->reader == null) {
			return true;
		}

		if (isset ($_SERVER ['REMOTE_ADDR'])) {
			$record = $this->reader->country ($_SERVER ['REMOTE_ADDR']);

			return isset ($record->country->isInEuropeanUnion) && $record->country->isInEuropeanUnion ? true : false;
		}

		return false;
	}

}
