<?php
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

namespace ChylyGDPRCookies;

class Loader {

	public static $Instance = null;

	private $mainDirectory = null;
	private $ignoreDirectory = ['GeoIp2', 'Page'];
	private $ignoreFiles = ['Loader', 'polylang', 'uninstall', 'psdc-gdpr-cookies'];

	/**
	 * Loader initialization.
	 */
	public function __construct () {
		if (self::$Instance != null) {
			return;
		}

		self::$Instance = $this;

		$this->mainDirectory = dirname (__FILE__);

		spl_autoload_register (['ChylyGDPRCookies\Loader', 'AutoloadClass']);
	}

	/**
	 * Custom autoload method for autoloading class files..
	 */
	public static function AutoloadClass ($className) {
		if (strpos ($className, 'GeoIp2') !== false || strpos ($className, 'MaxMind') !== false) {
			$filePath = str_replace ('\\', '/', dirname (__FILE__) . '/' . $className . '.php');

			if (file_exists ($filePath)) {
				require_once ($filePath);
			}
		}
	}

	/**
	 * Remove directory with all of its contents.
	 */
	public static function RemoveDirectory ($directoryPath) {
		$files = scandir ($directoryPath);

		foreach ($files as $i => $v) {
			if (!in_array ($v, ['.', '..'])) {
				$path = $directoryPath . '/' . $v;

				if (is_dir ($path)) {
					self::RemoveDirectory ($path);
				} else {
					unlink ($path);
				}
			}
		}

		rmdir ($directoryPath);
	}

	/**
	 * Scan directory for PHP files and load them.
	 */
	public function LoadDirectory ($directoryPath = null) {
		if ($directoryPath == null) {
			$directoryPath = $this->mainDirectory;
		}

		foreach ($this->ignoreDirectory as $i => $v) {
			if (strpos ($directoryPath, $v) !== false) {
				return;
			}
		}

		$files = scandir ($directoryPath);
		$subDirectoriesToLoad = [];

		foreach ($files as $i => $v) {
			if (!in_array ($v, ['.', '..'])) {
				$path = $directoryPath . '/' . $v;

				if (is_dir ($path)) {
					$subDirectoriesToLoad [] = $path;
				} else {
					if (strpos ($v, '.php') !== false) {
						$ignore = false;
						foreach ($this->ignoreFiles as $ii => $iv) {
							if (strpos ($v, $iv) !== false) {
								$ignore = true;
								break;
							}
						}

						if ($ignore) {
							continue;
						}

						require_once ($path);
					}
				}
			}
		}

		if (count ($subDirectoriesToLoad) > 0) {
			foreach ($subDirectoriesToLoad as $i => $v) {
				$this->LoadDirectory ($v);
			}
		}
	}

}

new Loader ();
Loader::$Instance->LoadDirectory ();
