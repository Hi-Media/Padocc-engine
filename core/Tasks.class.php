<?php

class Tasks {

	private static $TYPES = array('base', 'extended');

	private static $AVAILABLE_TASKS = array();

	/**
	 * Retourne un tableau associatif décrivant les tâches disponibles.
	 *
	 * @return array tableau associatif des tâches disponibles : array('sTag' => 'sClassName', ...)
	 */
	private static function getAvailableTasks () {
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

	/**
	 * Retourne la liste des instances de tâches correspondant à chacune des tâches XML devant être exécutée
	 * à l'intérieur du noeud XML spécifié.
	 *
	 * @param SimpleXMLElement $oTarget
	 * @param Task_Base_Project $oProject
	 * @param string $sBackupPath
	 * @param Shell_Interface $oShell
	 * @param Logger_Interface $oLogger
	 * @return array liste d'instances de type Task
	 * @throws Exception si tag XML inconnu.
	 * @see Task
	 */
	public static function getTaskInstances (SimpleXMLElement $oTarget, Task_Base_Project $oProject, $sBackupPath, Shell_Interface $oShell, Logger_Interface $oLogger) {
		$oLogger->log('Initialize tasks...' . "\n");
		$aAvailableTasks = self::getAvailableTasks();

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
				$aTaskInstances[] = new $aAvailableTasks[$sTag]($oTask, $oProject, $sBackupPath, $oShell, $oLogger);
			}
		}

		return $aTaskInstances;
	}

	public static function getProject ($sProjectName) {
		$sProjectFilename = DEPLOYMENT_RESOURCES_DIR . '/' . $sProjectName . '.xml';
		if ( ! file_exists($sProjectFilename)) {
			throw new Exception("Project definition not found: '$sProjectFilename'!");
		}

		$oProject = new SimpleXMLElement($sProjectFilename, NULL, true);
		if ((string)$oProject['name'] !== $sProjectName) {
			throw new Exception("Project's attribute name ('" . $oProject['name'] . "') must be eqal to project filename ('$sProjectName').");
		}

		return $oProject;
	}

	public static function getTarget (SimpleXMLElement $oProject, $sEnvName) {
		$aTargets = $oProject->xpath("target[@name='$sEnvName']");
		if (count($aTargets) !== 1) {
			throw new Exception("Environment '$sEnvName' not found or not unique in project!");
		}
		return $aTargets[0];
	}

	/**
	 * Classe outil : pas d'instance.
	 */
	private function __construct () {}
}