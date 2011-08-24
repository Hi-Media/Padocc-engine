<?php

class Task_Base_MkDir extends Task
{

    /**
     * Retourne le nom du tag XML correspondant à cette tâche dans les config projet.
     *
     * @return string nom du tag XML correspondant à cette tâche dans les config projet.
     */
    public static function getTagName ()
    {
        return 'mkdir';
    }

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
        $this->aAttributeProperties = array(
            'destdir' => Task::ATTRIBUTE_DIR | Task::ATTRIBUTE_REQUIRED | Task::ATTRIBUTE_ALLOW_PARAMETER,
            'mode' => 0
        );
    }

    protected function _centralExecute ()
    {
        parent::_centralExecute();
        $this->oLogger->indent();
        $sMode = (empty($this->aAttributes['mode']) ? '' : $this->aAttributes['mode']);

        $aDestDirs = $this->_processPath($this->aAttributes['destdir']);
        foreach ($aDestDirs as $sDestDir) {
            $this->oShell->mkdir($sDestDir, $sMode);
        }
        $this->oLogger->unindent();
    }

    public function backup ()
    {
    }
}
