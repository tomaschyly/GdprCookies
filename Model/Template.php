<?php
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

namespace ChylyGDPRCookiesModel;

class Template {

	protected $name = '';
	protected $html = '';

	/**
	 * Template initialization.
	 */
	public function __construct ($name) {
		$this->name = $name;

		if (file_exists (self::TemplateFilePath ($name))) {
			$this->html = file_get_contents (self::TemplateFilePath ($name));
			$this->InsertStandard ();
		}
	}

	/**
	 * Get template filePath for name.
	 */
	public static function TemplateFilePath ($name) {
		return \ChylyGDPRCookies::GetPluginDirPath () . 'Template/' . $name . '.html';
	}

	/**
	 * Insert standard values to html.
	 */
	private function InsertStandard () {
		$this->Insert ('pluginID', \ChylyGDPRCookies::ID);
	}

	/**
	 * Insert value into html.
	 */
	public function Insert ($key, $value) {
		$this->html = str_replace ('{$' . $key . '}', $value, $this->html);
	}

	/**
	 * Generate and insert options for select from list.
	 */
	public function InsertOptions ($key, $options, $selected = null) {
		$html = '';
		foreach ($options as $i => $v) {
			if ($i == $selected) {
				$html .= '<option value="' . $i . '" selected="selected">' . $v . '</option>';
			} else {
				$html .= '<option value="' . $i . '">' . $v . '</option>';
			}
		}

		$this->html = str_replace ('{$' . $key . '}', $html, $this->html);
	}

	/**
	 * Render the template html content.
	 */
	public function Render () {
		echo $this->html; //TODO refactor into eval() after all
	}

}
