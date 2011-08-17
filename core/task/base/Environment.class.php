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
			'withsymlinks' => Task::ATTRIBUTE_BOOLEAN,
			'basedir' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED
		));

		$this->oProperties->setProperty('basedir', $this->aAttributes['basedir']);
		$sWithSymlinks = (empty($this->aAttributes['withsymlinks']) ? 'false' : $this->aAttributes['withsymlinks']);
		$this->oProperties->setProperty('withsymlinks', $sWithSymlinks);

		// Création de switch de symlink sous-jacente :
		if ($this->oProperties->getProperty('withsymlinks') === 'true') {
			$this->oNumbering->addCounterDivision();
			$sBaseSymLink = $this->oProperties->getProperty('basedir');
			$sReleaseSymLink = $sBaseSymLink . '_releases/' . $this->oProperties->getProperty('execution_id');
			$this->oLinkTask = Task_Base_Link::getNewInstance(array(
				'src' => $sBaseSymLink,
				'target' => $sReleaseSymLink,
				'server' => '${SERVERS_CONCERNED_WITH_SYMLINKS}'
			), $oProject, $sBackupPath, $oServiceContainer);
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
	}

	private $_aPathsToHandle;

	private function analyzeRegisteredPaths () {
		$this->_aPathsToHandle = array();
		$aPaths = array_keys(self::$aRegisteredPaths);
		//$this->oLogger->log(print_r($aPaths, true));

		$sBaseSymLink = $this->oProperties->getProperty('basedir');
		foreach ($aPaths as $sPath) {
			$aExpandedPaths = $this->_expandPath($sPath);
			foreach ($aExpandedPaths as $sExpandedPath) {
				list($bIsRemote, $aMatches) = $this->oShell->isRemotePath($sExpandedPath);
				if ($bIsRemote && strpos($aMatches[2], $sBaseSymLink) !== false) {
					$this->_aPathsToHandle[$aMatches[1]][] = $aMatches[2];
				}
			}
		}

		//$this->oLogger->log(print_r($this->_aPathsToHandle, true));
		$aServersConcernedWithSymlinks = array_keys($this->_aPathsToHandle);
		sort($aServersConcernedWithSymlinks);
		$this->oLogger->log("Servers concerned with symlinks: '" . implode("', '", $aServersConcernedWithSymlinks) . "'.");
		$this->oProperties->setProperty('SERVERS_CONCERNED_WITH_SYMLINKS', implode(' ', $aServersConcernedWithSymlinks));
	}

	private function makeTransitionToSymlinks () {
		$this->oProperties->setProperty('symlink', '');
		$sBaseSymLink = $this->oProperties->getProperty('basedir');
		$sPath = '${SERVERS_CONCERNED_WITH_SYMLINKS}' . ':' . $sBaseSymLink;
		foreach ($this->_expandPath($sPath) as $sExpandedPath) {
			if ($this->oShell->getFileStatus($sExpandedPath) === 2) {
				list(, $aMatches) = $this->oShell->isRemotePath($sExpandedPath);
				$sDir = $sExpandedPath . '/*';
				$sOriginRelease = $aMatches[1] . ':' . $sBaseSymLink . '_releases/' . $this->oProperties->getProperty('execution_id') . '_origin';
				$this->oLogger->log("Backup '$sDir' to '$sOriginRelease'.");
				$this->oShell->copy($sDir, $sOriginRelease);
				$this->oShell->remove($sExpandedPath);
				$this->oShell->createLink($sExpandedPath, $sOriginRelease);
			}
		}
		$this->oProperties->setProperty('symlink', $this->aAttributes['withsymlink']);
	}

	private function initNewRelease () {
		$this->oProperties->setProperty('symlink', '');

		$sBaseSymLink = $this->oProperties->getProperty('basedir');
		$sPath = '${SERVERS_CONCERNED_WITH_SYMLINKS}' . ':' . $sBaseSymLink;
		$sReleaseSymLink = $sBaseSymLink . '_releases/' . $this->oProperties->getProperty('execution_id');
		foreach ($this->_expandPath($sPath) as $sExpandedPath) {
			list(, $aMatches) = $this->oShell->isRemotePath($sExpandedPath);
			$sDir = $sExpandedPath . '/*';
			$sDest = $aMatches[1] . ':' . $sReleaseSymLink;
			if ($this->oShell->getFileStatus($sExpandedPath) === 12) {
				$this->oLogger->log("Initialize '$sDest' with previous deployment: '$sExpandedPath'.");
				$this->oShell->copy($sDir, $sDest);
			} else {
				$this->oLogger->log("No previous deployment to initialize '$sDest'.");
			}
		}

		$this->oProperties->setProperty('symlink', $this->aAttributes['withsymlink']);
	}

	public function setUp () {
		if ( ! empty($this->aAttributes['withsymlink'])) {
			array_push($this->aTasks, $this->oLinkTask);
		}

		parent::setUp();

		if ( ! empty($this->aAttributes['withsymlink'])) {
			array_pop($this->aTasks);
		}
	}

	protected function _addMailTo () {
		$this->oLogger->indent();
		$this->analyzeRegisteredPaths();
		if ( ! empty($this->aAttributes['withsymlink'])) {
			$this->makeTransitionToSymlinks();
			$this->initNewRelease();
		} else {
			$this->makeTransitionFromSymlinks();
		}
		$this->oLogger->unindent();

		parent::_addMailTo();
	}

	public function execute () {
		parent::execute();

		if ( ! empty($this->aAttributes['withsymlink'])) {
			$this->oProperties->setProperty('symlink', '');
			$this->oLinkTask->execute();
			$this->oProperties->setProperty('symlink', $this->aAttributes['withsymlink']);
		}
	}
}
