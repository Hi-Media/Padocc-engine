<?php

class Task_Extended_BuildLanguage extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'buildlanguage';
	}

	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'project' => array('required'),
			'destdir' => array('dir', 'required', 'allow_parameters')
		);
	}

	public function check () {
		parent::check();
	}

	public function execute () {
		$aCurlParameters = array(
			'url' => 'https://admin.twenga.com/translation_tool/build_language_files2.php?project=rts',
			'login' => 'gaubry',
			'password' => 'xxx',
			'user_agent' => Curl::$USER_AGENTS['FireFox3'],
			'referer' => 'http://aai.twenga.com',
			//'header' => array('Expect:'),
			//'file' => $fh,
			//'return_header' => 0,
		);
		$result = Curl::disguiseCurl($aCurlParameters);
		var_dump($result);
		if ( ! empty($result['curl_error'])) {
			throw new RuntimeException($result['curl_error']);
		} else if ($result['http_code'] < 200 || $result['http_code'] >= 300) {
			throw new RuntimeException(
				'Return HTTP code: ' . $result['http_code']
				. '. Last URL: ' . $result['last_url']
				. '. Body: ' . $result['body']
			);
		}
	}

	public function backup () {
	}
}