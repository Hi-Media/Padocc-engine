<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY <geoffroy.aubry@twenga.com>
 */
class Task_Extended_TwengaServers extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'twengaserverexport';
    }

    /**
     * Tâche d'export Git sous-jacente.
     * @var Task_Extended_GitExport
     */
    private $_oGitExportTask;

    private $_sTmpDir;

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $oServiceContainer);
        $this->_aAttrProperties = array();
        $this->_sTmpDir = DEPLOYMENT_TMP_PATH . '/'
                        . $this->_oProperties->getProperty('execution_id') . '_' . self::getTagName();

        // Création de la tâche de synchronisation sous-jacente :
        $this->_oNumbering->addCounterDivision();
        $this->_oGitExportTask = Task_Extended_GitExport::getNewInstance(
            array(
                'repository' => 'git@git.twenga.com:aa/server_config.git',
                'ref' => 'master',
                'destdir' => $this->_sTmpDir
            ),
            $oProject,
            $oServiceContainer
        );
        $this->_oNumbering->removeCounterDivision();
    }

    /**
     * Prépare la tâche avant exécution : vérifications basiques, analyse des serveurs concernés...
     */
    public function setUp ()
    {
        parent::setUp();
        $this->_oLogger->indent();
        $this->_oGitExportTask->setUp();
        $this->_oLogger->unindent();
    }

    /**
     * Phase de traitements centraux de l'exécution de la tâche.
     * Elle devrait systématiquement commencer par "parent::_centralExecute();".
     * Appelé par _execute().
     * @see execute()
     */
    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();
        $this->_oGitExportTask->execute();
        $sPathToLoad = $this->_sTmpDir . '/master_synchro.cfg';
        $this->_oLogger->log('Load shell properties: ' . $sPathToLoad);
        $this->_oLogger->indent();
        $this->_oProperties->loadConfigShellFile($sPathToLoad);
        $this->_oShell->remove($this->_sTmpDir);
        $this->_oLogger->unindent();
        $this->_oLogger->unindent();
    }
}
