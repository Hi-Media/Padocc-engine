<?php

class Task_Base_Project extends Task_Base_Call {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'project';
	}

	private $sEnvName;

	public function __construct ($sProjectName, $sEnvName, $sExecutionID, ServiceContainer $oServiceContainer) {
		$sBackupPath = DEPLOYMENT_BACKUP_DIR . '/' . $sExecutionID;
		$oProject = Tasks::getProject($sProjectName);
		$this->sEnvName = $sEnvName;

		parent::__construct($oProject, $this, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array(
			'name' => array('required'),
			'propertyinifile' => array('srcpath'),
			'propertyshellfile' => array('srcpath'),
		);

		$this->oProperties->addProperty('project_name', $sProjectName);
		$this->oProperties->addProperty('environment_name', $sEnvName);
	}

	protected function getBoundTask ($sBackupPath) {
		$sEnvName = $this->sEnvName;
		$aTargets = $this->oProject->getSXE()->xpath("env[@name='$sEnvName']");
		if (count($aTargets) !== 1) {
			throw new Exception("Environment '$sEnvName' not found or not unique in this project!");
		}
		return new Task_Base_Environment($aTargets[0], $this->oProject, $sBackupPath, $this->oServiceContainer);
	}

	public function check () {
		parent::check();
	}

	public function execute () {
		//$this->sendStartMail();
		parent::execute();
		//$this->sendEndMail();
	}

	private function sendStartMail () {
		$sProjectName = $this->oProperties->getProperty('project_name');
		$sEnvName = $this->oProperties->getProperty('environment_name');
		$sNow = date('Y-m-d H:i:s');

		$aTo = array('geoff.abury@gmail.com', 'geoffroy.aubry@twenga.com');
		$sFrom = 'deployement@twenga.com';
		$sSubject = "[Deployment][Start] Project: $sProjectName, Env: $sEnvName";
		$sMessage =
			"<h2>New depoyment started</h2>"
			. "Deployment of project <b>$sProjectName</b> on environment <b>$sEnvName</b> has just started at $sNow <i>(Y-m-d H:i:s)</i>.<br />"
			. "You will receive another mail at the end of process.";

		$result = $this->oMail->send($aTo, $sSubject, $sMessage, $sFrom, AbstractMail::HIGH_PRIORITY, array(), 'utf-8');
		if ($result === FALSE) {
			throw new Exception('Email not sent!');
		}
	}

	// mutt -s "This is a subject" -a /home/gaubry/supervisor/logs/deployment.php.20110704161807_29157.info.log -- geoff.abury@gmail.com geoffroy.aubry@twenga.com < <(tail /home/gaubry/supervisor/logs/supervisor.info.log)
	private function sendEndMail () {
		$sProjectName = $this->oProperties->getProperty('project_name');
		$sEnvName = $this->oProperties->getProperty('environment_name');
		$sNow = date('Y-m-d H:i:s');

		$aTo = array('geoff.abury@gmail.com', 'geoffroy.aubry@twenga.com');
		$sFrom = 'deployement@twenga.com';

		//file_put_contents("$csv_file.gz", gzencode(file_get_contents($csv_file), 9));
		//$attachments = array("$csv_file.gz");
		$aAttachments = array();

		$sSubject = "[Deployment][End] Project: $sProjectName, Env: $sEnvName";
		$sMessage =
			"<h2>End of depoyment</h2>"
			. "Deployment of project <b>$sProjectName</b> on environment <b>$sEnvName</b> has just finished at $sNow <i>(Y-m-d H:i:s)</i>.<br />"
			. "";

		$result = $this->oMail->send($aTo, $sSubject, $sMessage, $sFrom, AbstractMail::HIGH_PRIORITY, $aAttachments, 'utf-8');
		foreach ($aAttachments as $file) {
			unlink($file);
		}

		if ($result === FALSE) {
			throw new Exception('Email not sent!');
		}
	}

	public function backup () {}

	public function getSXE () {
		return $this->oTask;
	}
}
