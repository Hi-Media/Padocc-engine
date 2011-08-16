<?php

class Task_Base_Environment extends Task_Base_Target {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'env';
	}

	private $oCopyTask;
	private $oLinkTask;

	/**
	 * Constructeur.
	 *
	 * @param SimpleXMLElement $oTask Contenu XML de la tâche.
	 * @param Task_Base_Project $oProject Super tâche projet.
	 * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
	 * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
	 */
	public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer) {
		parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
		$this->aAttributeProperties = array_merge($this->aAttributeProperties, array(
			'name' => Task::ATTRIBUTE_REQUIRED,
			'mailto' => 0,
			'withsymlink' => 0
		));

		// Création de switch de symlink sous-jacente :
		if ( ! empty($this->aAttributes['withsymlink'])) {
			$this->oNumbering->addCounterDivision();
			/*$sSrcDir = preg_replace('#/$#', '', $this->aAttributes['srcdir']) . '/*';
			$this->oCopyTask = Task_Base_Copy::getNewInstance(array(
				'src' => $sSrcDir,
				'destdir' => $this->aAttributes['destdir']
			), $oProject, $sBackupPath, $oServiceContainer);*/
			/*$this->oCopyTask = Task_Base_Copy::getNewInstance(array(
				'src' => '${xxx}',
				'destdir' => '${yyy}'
			), $oProject, $sBackupPath, $oServiceContainer);
			array_unshift($this->aTasks, $this->oCopyTask);*/

			$sBaseSymLink = $this->aAttributes['withsymlink'];
			$sReleaseSymLink = $sBaseSymLink . '_releases/' . $this->oProperties->getProperty('execution_id');
			$this->oLinkTask = Task_Base_Link::getNewInstance(array(
				'src' => $sBaseSymLink,
				'target' => $sReleaseSymLink,
				'server' => '${SERVERS_CONCERNED_WITH_SYMLINKS}'
			), $oProject, $sBackupPath, $oServiceContainer);
			array_push($this->aTasks, $this->oLinkTask);
			$this->oNumbering->removeCounterDivision();
		}
	}

	/**
	 * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
	 * Lance une exception si tel n'est pas le cas.
	 *
	 * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
	 * doit permettre de remonter au plus tôt tout dysfonctionnement.
	 * Appelé avant la méthode execute().
	 *
	 * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
	 * @throws DomainException en cas de valeur non permise
	 */
	public function check () {
		parent::check();
		if ( ! empty($this->aAttributes['withsymlink'])) {
			$this->oProperties->addProperty('symlink', $this->aAttributes['withsymlink']);
		}
	}

	private $_aPathsToHandle;

	private function analyzeRegisteredPaths () {
		$this->_aPathsToHandle = array();
		$aPaths = array_keys(self::$aRegisteredPaths);
		$this->oLogger->log(print_r($aPaths, true));

		$sBaseSymLink = $this->oProperties->getProperty('symlink');
		foreach ($aPaths as $sPath) {
			$aExpandedPaths = $this->_expandPath($sPath);
			foreach ($aExpandedPaths as $sExpandedPath) {
				list($bIsRemote, $aMatches) = $this->oShell->isRemotePath($sExpandedPath);
				if ($bIsRemote && strpos($aMatches[2], $sBaseSymLink) !== false) {
					$this->_aPathsToHandle[$aMatches[1]][] = $aMatches[2];
				}
			}
		}

		$this->oLogger->log(print_r($this->_aPathsToHandle, true));
		$this->oProperties->addProperty('SERVERS_CONCERNED_WITH_SYMLINKS', array_keys($this->_aPathsToHandle));
	}

	protected function _addMailTo () {
		$this->analyzeRegisteredPaths();
		parent::_addMailTo();
	}

	public function execute () {
		parent::execute();

	}
}
