<?php

class Task_Extended_GitExport extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'gitexport';
    }

    /**
     * Tâche de synchronisation sous-jacente.
     * @var Task_Base_Sync
     */
    private $oSyncTask;

    /**
     * Constructeur.
     *
     * @param SimpleXMLElement $oTask Contenu XML de la tâche.
     * @param Task_Base_Project $oProject Super tâche projet.
     * @param string $sBackupPath répertoire hôte pour le backup de la tâche.
     * @param ServiceContainer $oServiceContainer Register de services prédéfinis (Shell_Interface, Logger_Interface, ...).
     */
    public function __construct (SimpleXMLElement $oTask, Task_Base_Project $oProject, $sBackupPath, ServiceContainer $oServiceContainer)
    {
        parent::__construct($oTask, $oProject, $sBackupPath, $oServiceContainer);
        $this->aAttributeProperties = array(
            'repository' => Task::ATTRIBUTE_FILE | Task::ATTRIBUTE_REQUIRED,
            'ref' => Task::ATTRIBUTE_REQUIRED | Task::ATTRIBUTE_ALLOW_PARAMETER,
            'srcdir' => Task::ATTRIBUTE_DIR,
            'destdir' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED | Task::ATTRIBUTE_ALLOW_PARAMETER,
            'exclude' => Task::ATTRIBUTE_FILEJOKER
        );

        if (empty($this->aAttributes['srcdir'])) {
            $this->aAttributes['srcdir'] =
                DEPLOYMENT_REPOSITORIES_DIR . '/git/'
                . $this->oProperties->getProperty('project_name') . '_'
                . $this->oProperties->getProperty('environment_name') . '_'
                . $this->sCounter;
        }

        // Création de la tâche de synchronisation sous-jacente :
        $this->oNumbering->addCounterDivision();
        $sSrcDir = preg_replace('#/$#', '', $this->aAttributes['srcdir']) . '/*';
        $this->oSyncTask = Task_Base_Sync::getNewInstance(array(
            'src' => $sSrcDir,
            'destdir' => $this->aAttributes['destdir'],
            'exclude' => $this->aAttributes['exclude']
        ), $oProject, $sBackupPath, $oServiceContainer);
        $this->oNumbering->removeCounterDivision();
    }

    public function setUp ()
    {
        parent::setUp();
        $this->oLogger->indent();
        $this->oSyncTask->setUp();
        $this->oLogger->unindent();
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->oLogger->indent();

        $aRef = $this->_processPath($this->aAttributes['ref']);
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

        $this->oSyncTask->execute();
        $this->oLogger->unindent();
    }

    public function backup ()
    {
        /*if ($this->oShell->getFileStatus($this->aAttributes['destdir']) !== 0) {
            list($bIsRemote, $aMatches) = $this->oShell->isRemotePath($this->aAttributes['destdir']);
            $sBackupPath = ($bIsRemote ? $aMatches[1]. ':' : '') . $this->sBackupPath . '/'
                . pathinfo($aMatches[2], PATHINFO_BASENAME) . '.tar.gz';
            $this->oShell->backup($this->aAttributes['destdir'], $sBackupPath);
        }*/
    }
}
