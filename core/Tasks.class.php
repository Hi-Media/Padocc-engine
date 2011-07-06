<?php

class Tasks {

	private static $TYPES = array('base', 'extended');

	private static $AVAILABLE_TASKS = array();

	/**
	 * Retourne un tableau associatif décrivant les tâches disponibles.
	 *
	 * @return array tableau associatif des tâches disponibles : array('sTag' => 'sClassName', ...)
	 */
	public static function getAvailableTasks () {
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
					} else if ($sTag != 'project') {
						$aAvailableTasks[$sTag] = $sFullClassName;
					}
				}
			}
			self::$AVAILABLE_TASKS = $aAvailableTasks;
		}
		return self::$AVAILABLE_TASKS;
	}

	public static function getProject ($sProjectName) {
		$sProjectFilename = DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml';
		if ( ! file_exists($sProjectFilename)) {
			throw new Exception("Project definition not found: '$sProjectFilename'!");
		}
		$oProject = new SimpleXMLElement($sProjectFilename, NULL, true);
		return $oProject;
	}

	public static function getAllProjectsName () {
		$aProjectName = array();
		if ($handle = opendir(DEPLOYMENT_RESOURCES_DIR)) {
		    while ($file = readdir($handle)) {
		        clearstatcache();
		        $sProjectFilename = DEPLOYMENT_RESOURCES_DIR.'/'.$file;
		        if (substr($file, strlen($file)-3, 3) == "xml" && is_file($sProjectFilename)) {
		        	$oProject = new SimpleXMLElement($sProjectFilename, NULL, true);
					if (isset($oProject['name'])) {
						$aProjectName[] = (string)$oProject['name'];
					}
		        }
		    }
		    closedir($handle);
		}

		return $aProjectName;
	}

	public static function getAvailableTargetsList ($sProjectName) {
		$oProject = self::getProject($sProjectName);
		$aTargets = $oProject->xpath("//env");
		$aTargetsList = array();
		foreach ($aTargets as $aTarget) {
			$aTargetsList[] = (string)$aTarget['name'];
		}
		return $aTargetsList;
	}

	/**
	 * Classe outil : pas d'instance.
	 */
	private function __construct () {}
}