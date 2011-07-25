<?php

class Task_Extended_GitExport extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'gitexport';
	}

	private $oSyncTask;

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
		$this->aAttributeProperties = array(
			'repository' => array('file', 'required'),
			'ref' => array('required', 'allow_parameters'),
			'srcdir' => array('dir'),
			'destdir' => array('dir', 'required', 'allow_parameters'),
			'exclude' => array('filejoker'),
		);

		if (empty($this->aAttributes['srcdir'])) {
			$this->aAttributes['srcdir'] =
				DEPLOYMENT_REPOSITORIES_DIR . '/git/'
				. $this->oProperties->getProperty('project_name') . '_'
				. $this->oProperties->getProperty('environment_name') . '_'
				. $this->sCounter;
		}

		$this->oNumbering->addCounterDivision();
		$this->oSyncTask = Task_Base_Sync::getInstance(array(
			'src' => $this->aAttributes['srcdir'] . '/*',
			'destdir' => $this->aAttributes['destdir'],
			'exclude' => $this->aAttributes['exclude']
		), $oProject, $sBackupPath, $oServiceContainer);
		$this->oNumbering->removeCounterDivision();
	}

	/**
	 * Vérifie au moyen de tests basiques que la tâche peut être exécutée.
	 * Lance une exception si tel n'est pas le cas.
	 *
	 * Comme toute les tâches sont vérifiées avant que la première ne soit exécutée,
	 * doit permettre de remonter au plus tôt tout dysfonctionnement.
	 * Appelé avant la méthode execute().
	 *
	 * @throws UnexpectedValueException
	 * @throws DomainException
	 * @throws RuntimeException
	 */
	public function check () {
		parent::check();
		$this->oLogger->indent();
		$this->oSyncTask->check();
		$this->oLogger->unindent();
	}

	public function execute () {
		parent::execute();
		$this->oLogger->indent();

		$aRef = $this->_expandPaths($this->aAttributes['ref']);
		$sRef = $aRef[0];

		$this->oLogger->log("Export '$sRef' reference from '" . $this->aAttributes['repository'] . "' git repository");
		$this->oLogger->indent();
		$result = $this->oShell->exec(
			DEPLOYMENT_BASH_PATH . ' ' . DEPLOYMENT_LIB_DIR . '/gitexport.inc.sh'
			. ' "' . $this->aAttributes['repository'] . '"'
			. ' "' . $sRef . '"'
			. ' "' . $this->aAttributes['srcdir'] . '"'
		);
		$this->oLogger->log(implode("\n", $result));
		$this->oLogger->unindent();

		//$this->oLogger->log("Synchronize with '" . $this->aAttributes['destdir'] . "'");
		$this->oSyncTask->execute();
		/*$aExcludedPaths = (empty($this->aAttributes['exclude']) ? array() : explode(' ', $this->aAttributes['exclude']));
		$results = $this->oShell->sync($this->aAttributes['srcdir'] . '/*', $this->_expandPaths($this->aAttributes['destdir']), $aExcludedPaths);
		foreach ($results as $result) {
			$this->oLogger->log($result);
		}*/

		$this->oLogger->unindent();
	}

	public function backup () {
		/*if ($this->oShell->getFileStatus($this->aAttributes['destdir']) !== 0) {
			list($bIsRemote, $aMatches) = $this->oShell->isRemotePath($this->aAttributes['destdir']);
			$sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath . '/'
				. pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
			$this->oShell->backup($this->aAttributes['destdir'], $sBackupPath);
		}*/
	}
}