<?php

class Task_Extended_TwengaServers extends Task {

	/**
	 * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
	 *
	 * @return string nom du tag XML correspondant à cette tâche dans les config projet.
	 */
	public static function getTagName () {
		return 'twengaserverexport';
	}

	/**
	 * Tâche d'export Git sous-jacente.
	 * @var Task_Extended_GitExport
	 */
	private $oGitExportTask;

	private $sTmpDir;

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
		$this->aAttributeProperties = array();
		$this->sTmpDir = '/tmp/' . $this->oProperties->getProperty('execution_id') . '_' . self::getTagName();

		// Création de la tâche de synchronisation sous-jacente :
		$this->oNumbering->addCounterDivision();
		$this->oGitExportTask = Task_Extended_GitExport::getNewInstance(array(
			'repository' => 'git@git.twenga.com:aa/server_config.git',
			'ref' => 'master',
			'destdir' => $this->sTmpDir,
			'exclude' => ''
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
	 * @throws UnexpectedValueException en cas d'attribut ou fichier manquant
	 * @throws DomainException en cas de valeur non permise
	 */
	public function check () {
		parent::check();
		$this->oLogger->indent();
		$this->oGitExportTask->check();
		$this->oLogger->unindent();
	}

	public function execute () {
		parent::execute();
		$this->oLogger->indent();
		$this->oGitExportTask->execute();
		$sPathToLoad = $this->sTmpDir . '/master_synchro.cfg';
		$this->oLogger->log('Load shell properties: ' . $sPathToLoad);
		$this->oProperties->loadConfigShellFile($sPathToLoad);
		$this->oShell->remove($this->sTmpDir);
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