<?php

/**
 * @category TwengaDeploy
 * @package Core
 * @author Geoffroy AUBRY
 */
class Task_Extended_CVSExport extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'cvsexport';
    }

    /**
     * Tâche de synchronisation sous-jacente.
     * @var Task_Base_Sync
     */
    private $_oSyncTask;

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath,
        ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->_aAttributeProperties = array(
            'repository' => Task::ATTRIBUTE_FILE | Task::ATTRIBUTE_REQUIRED,
            'module' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED,
            'srcdir' => Task::ATTRIBUTE_DIR,
            'destdir' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED | Task::ATTRIBUTE_ALLOW_PARAMETER
        );

        if (empty($this->_aAttributes['srcdir'])) {
            $this->_aAttributes['srcdir'] =
                DEPLOYMENT_REPOSITORIES_DIR . '/cvs/'
                . $this->_oProperties->getProperty('project_name') . '_'
                . $this->_oProperties->getProperty('environment_name') . '_'
                . $this->_sCounter;
        }

        // Création de la tâche de synchronisation sous-jacente :
        $this->_oNumbering->addCounterDivision();
        $sSrcDir = preg_replace('#/$#', '', $this->_aAttributes['srcdir']) . '/' . $this->_aAttributes['module'] . '/*';
        $this->_oSyncTask = Task_Base_Sync::getNewInstance(
            array(
                'src' => $sSrcDir,
                'destdir' => $this->_aAttributes['destdir']
            ),
            $oProject, $sBackupPath, $oServiceContainer
        );
        $this->_oNumbering->removeCounterDivision();
    }

    public function setUp ()
    {
        parent::setUp();
        $this->_oLogger->indent();
        $this->_oSyncTask->setUp();
        $this->_oLogger->unindent();
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->_oLogger->indent();

        $this->_oLogger->log("Export from '" . $this->_aAttributes['repository'] . "' CVS repository");
        $this->_oLogger->indent();
        $result = $this->_oShell->exec(
            DEPLOYMENT_BASH_PATH . ' ' . DEPLOYMENT_LIB_DIR . '/cvsexport.inc.sh'
            . ' "' . $this->_aAttributes['repository'] . '"'
            . ' "' . $this->_aAttributes['module'] . '"'
            . ' "' . $this->_aAttributes['srcdir'] . '"'
        );
        $this->_oLogger->log(implode("\n", $result));
        $this->_oLogger->unindent();

        $this->_oSyncTask->execute();
        $this->_oLogger->unindent();
    }

    public function backup ()
    {
        /*if ($this->_oShell->getPathStatus($this->_aAttributes['destdir']) !== Shell_Interface::STATUS_NOT_EXISTS) {
            list($bIsRemote, $aMatches) = $this->_oShell->isRemotePath($this->_aAttributes['destdir']);
            $sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->_sBackupPath . '/'
                . pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
            $this->_oShell->backup($this->_aAttributes['destdir'], $sBackupPath);
        }*/
    }
}