<?php

class Tasks {

	private static $TYPES = array('base', 'extended');

	private static $AVAILABLE_TASKS = array();

	private static function _getAvailableTasks () {
		if (count(self::$AVAILABLE_TASKS) === 0) {
			$aAvailableTasks = array();
			foreach (self::$TYPES as $sTaskType) {
				$sTaskPaths = glob(DEPLOYMENT_TASKS_DIR . "/$sTaskType/*.class.php");
				foreach ($sTaskPaths as $sTaskPath) {
					$sClassName = strstr(substr(strrchr($sTaskPath, '/'), 1), '.', true);
					$sFullClassName = 'Task_' . ucfirst($sTaskType) . '_' . $sClassName;
					$sTag = $sFullClassName::getTagName();
					if (isset($aAvailableTasks[$sTag])) {
						throw new Exception("Already defined task tag '$sTag' in '$aAvailableTasks[$sTag]'!");
					} else {
						$aAvailableTasks[$sTag] = $sFullClassName;
					}
				}
			}
			self::$AVAILABLE_TASKS = $aAvailableTasks;
		}
		return self::$AVAILABLE_TASKS;
	}

	public static function getTaskInstances (SimpleXMLElement $oTarget, SimpleXMLElement $oProject, $sBackupPath) {
		$aAvailableTasks = self::_getAvailableTasks();

		// Mise à plat des tâches car SimpleXML regroupe celles successives de même nom
		// dans un tableau et les autres sont hors tableau :
		$aTasks = array();
		foreach ($oTarget->children() as $sTag => $mTasks) {
			if (is_array($mTasks)) {
				foreach ($mTasks as $oTask) {
					$aTasks[] = array($sTag, $oTask);
				}
			} else {
				$aTasks[] = array($sTag, $mTasks);
			}
		}

		// Création des instances de tâches :
		$aTaskInstances = array();
		foreach ($aTasks as $aTask) {
			list($sTag, $oTask) = $aTask;
			if ( ! isset($aAvailableTasks[$sTag])) {
				throw new Exception("Unkown task tag: '$sTag'!");
			} else {
				$aTaskInstances[] = new $aAvailableTasks[$sTag]($oTask, $oProject, $sBackupPath);
			}
		}

		return $aTaskInstances;
	}

	public static function getProject ($sProjectName) {
		$sProjectFilename = DEPLOYMENT_PROJECTS_DIR . '/' . $sProjectName . '.xml';
		if ( ! file_exists($sProjectFilename)) {
			throw new Exception("Project definition not found: '$sProjectFilename'!");
		}

		$oProject = new SimpleXMLElement($sProjectFilename, NULL, true);
		if ((string)$oProject['name'] !== $sProjectName) {
			throw new Exception("Project's attribute name ('" . $oProject['name'] . "') must be eqal to project filename ('$sProjectName').");
		}

		return $oProject;
	}

	public static function getTarget (SimpleXMLElement $oProject, $sTargetName) {
		$aTargets = $oProject->xpath("target[@name='$sTargetName']");
		if (count($aTargets) !== 1) {
			throw new Exception("Target '$sTargetName' not found or not unique in project!");
		}
		return $aTargets[0];
	}

	/**
	 * Classe outil : pas d'instance.
	 */
	private function __construct () {}
}